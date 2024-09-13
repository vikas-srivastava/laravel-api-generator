<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Page;
use App\Models\Post;
use App\Models\Media;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FetchWordpressContent extends Command
{
    protected $signature = 'fetch:wordpress-content {url}';
    protected $description = 'Fetch and store WordPress content from a given URL';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $url = $this->argument('url');
        $slug = $this->getSlugFromUrl($url);

        $type = $this->choice('Is this a Post or a Page?', ['post', 'page'], 0);

        $apiUrl = $this->buildApiUrl($type, $url, $slug);
        $response = Http::get($apiUrl);

        if ($response->failed()) {
            $this->error('Failed to fetch content from WordPress API.');
            return 1;
        }

        $contentData = $response->json();

        if (empty($contentData)) {
            $this->error('No content found for the given slug.');
            return 1;
        }

        $contentItem = $contentData[0];

        if (!isset($contentItem['title']['rendered']) || !isset($contentItem['content']['rendered'])) {
            $this->error('The expected structure was not found in the WordPress API response.');
            return 1;
        }

        $title = $contentItem['title']['rendered'];
        $content = $contentItem['content']['rendered'];

        $userId = $this->getOrCreateDefaultUser();
        $categoryId = $this->getOrCreateDefaultCategory();

        $content = $this->handleMedia($content, $url, $userId);

        if ($type === 'page') {
            $this->savePage($title, $slug, $content, $userId);
        } else {
            $this->savePost($title, $slug, $content, $userId, $categoryId);
        }

        $this->info('Content fetched and stored successfully.');
        return 0;
    }

    protected function getSlugFromUrl($url)
    {
        return basename(parse_url($url, PHP_URL_PATH));
    }

    protected function buildApiUrl($type, $url, $slug)
    {
        $baseApiUrl = rtrim(parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST), '/');
        if ($type === 'page') {
            return "$baseApiUrl/wp-json/wp/v2/pages?slug=$slug";
        } else {
            return "$baseApiUrl/wp-json/wp/v2/posts?slug=$slug";
        }
    }

   

    protected function savePage($title, $slug, $content, $userId)
    {
        Page::create([
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'user_id' => $userId,
            'is_published' => false,
        ]);
    }

    protected function handleMedia($content, $url, $userId)
    {
        // Handle `src` attributes
        preg_match_all('/<img[^>]+src="([^"]+)"/i', $content, $srcMatches);
        foreach ($srcMatches[1] as $imgUrl) {
            if ($this->isUrlValid($imgUrl)) {
                $localPath = $this->downloadAndReplaceUrl($imgUrl, $url, $userId);
                if ($localPath) {
                    $content = str_replace($imgUrl, asset('storage/' . $localPath), $content);
                }
            } else {
                Log::warning("Skipped invalid URL: $imgUrl");
            }
        }

        // Handle `srcset` attributes
        preg_match_all('/<img[^>]+srcset="([^"]+)"/i', $content, $srcsetMatches);
        foreach ($srcsetMatches[1] as $srcset) {
            $newSrcset = $this->processSrcset($srcset, $url, $userId);
            $content = str_replace($srcset, $newSrcset, $content);
        }

        // Handle general `wp-content/uploads/` paths
        preg_match_all('/wp-content\/uploads\/([^\s"]+)/i', $content, $matches);
        foreach ($matches[0] as $file) {
            $fullUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/' . $file;
            $localPath = 'uploads/' . ltrim($file, 'wp-content/uploads/');
            if ($this->isUrlValid($fullUrl)) {
                $this->downloadFile($fullUrl, $localPath, $userId);
                $content = str_replace($file, 'storage/' . $localPath, $content);
            } else {
                Log::warning("Skipped invalid URL: $fullUrl");
            }
        }

        return $content;
    }

    protected function isUrlValid($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) && @get_headers($url);
    }

    protected function downloadAndReplaceUrl($imgUrl, $url, $userId)
    {
        $pathInfo = parse_url($imgUrl);
        if (!$this->isUrlValid($imgUrl)) {
            return false;
        }

        $relativePath = 'uploads/' . basename($pathInfo['path']);
        $downloaded = $this->downloadFile($imgUrl, $relativePath, $userId);

        return $downloaded ? $relativePath : false;
    }

    protected function downloadFile($url, $path, $userId)
    {
        try {
            $contents = @file_get_contents($url);

            if ($contents === false) {
                throw new \Exception("Failed to download file from $url.");
            }

            Storage::disk('public')->put($path, $contents);

            // Save media record in the database
            Media::create([
                'file_name' => basename($path),
                'file_path' => $path,
                'mime_type' => mime_content_type(storage_path('app/public/' . $path)),
                'size' => Storage::disk('public')->size($path),
                'user_id' => $userId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Error downloading file: " . $e->getMessage());
            return false;
        }
    }

    protected function processSrcset($srcset, $url, $userId)
    {
        $newSrcset = [];
        $srcsetParts = explode(',', $srcset);

        foreach ($srcsetParts as $srcsetPart) {
            $srcsetUrl = trim(explode(' ', trim($srcsetPart))[0]);
            if ($this->isUrlValid($srcsetUrl)) {
                $localPath = $this->downloadAndReplaceUrl($srcsetUrl, $url, $userId);
                if ($localPath) {
                    $newSrcset[] = asset('storage/' . $localPath) . ' ' . trim(str_replace($srcsetUrl, '', $srcsetPart));
                }
            } else {
                Log::warning("Skipped invalid srcset URL: $srcsetUrl");
            }
        }

        return implode(', ', $newSrcset);
    }

    protected function savePost($title, $slug, $content, $userId, $categoryId)
    {
        Post::create([
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'user_id' => $userId,
            'category_id' => $categoryId,
            'is_published' => false,
        ]);
    }

    protected function getOrCreateDefaultUser()
    {
        $user = User::firstOrCreate(
            ['email' => 'default@example.com'],
            [
                'name' => 'Default User',
                'password' => bcrypt('password'),
            ]
        );

        return $user->id;
    }

    protected function getOrCreateDefaultCategory()
    {
        $category = Category::firstOrCreate(
            ['name' => 'Uncategorized'],
            [
                'slug' => Str::slug('Uncategorized'),
                'description' => 'Default category',
            ]
        );

        return $category->id;
    }
}