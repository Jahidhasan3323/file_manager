<?php


namespace App\Services;


use App\Http\Controllers\FileManager;
use App\Rules\FileManagerFileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileManagerService
{
    private $response;
    private $relativePath;
    private $disc;
    private $perPage = 2;
    private $page = null;
    private $sortBy = 'asc';
    private $searchString = '';
    private $rootPath;

    public function __construct()
    {
        $this->disc = config('filesystems.disks.local.driver');
        $this->rootPath = config('filesystems.disks.local.root');
    }

    /**
     * @param string $type
     * @param false $isRoot
     */
    public function getResponseData($requestData)
    {
        $type = $requestData['type'] ?? 'directories';
        $isRoot = (boolean)($requestData['isRoot'] ?? true);
        $this->configData($requestData);
        return $this->getData($type, $isRoot);
    }

    /**
     * @param $requestData
     */
    private function configData($requestData){
        $this->relativePath = trim($requestData['relativePath'] ?? '/', '/');
        $this->perPage = $requestData['per_page'] ?? $this->perPage;
        $this->sortBy = $requestData['sort'] ?? $this->sortBy;
        $this->searchString = $requestData['search'] ?? $this->searchString;
    }

    public function getData($type = 'all', $isRoot = false)
    {
        $disc = $this->disc;

        $response = $this->getFromStorage($disc, $type)->filter()->addOptions();
//        dd($response);
        if ($isRoot) $response = $response->addRootOptions();
        return $response->getResponse();

    }

    /**
     * @param string $disc
     * @param string $fetch
     * @return FileManagerService
     */
    private function getFromStorage(string $disc = 'local', string $fetch = ''): FileManagerService
    {
        switch ($fetch) {
            case 'directories' :
            {
                $directories = Storage::disk($disc)->directories($this->relativePath);
                $response['directories'] = $this->getTrim($directories);
                break;
            }

            case
            'files' :
            {
                $files = Storage::disk($disc)->files($this->relativePath);
                $response['files'] = $this->getTrim($files);
                break;
            }
            case
            'all':
            {
                $directories = Storage::disk($disc)->directories($this->relativePath);
                $response['directories'] = $this->getTrim($directories);
                $files = Storage::disk($disc)->files($this->relativePath);
                $response['files'] = $this->getTrim($files);
                break;
            }
        }

        $this->response = $response;

        return $this;
    }

    /**
     * @param $paths
     * @return array
     */
    private function getTrim($paths): array
    {
        return array_map(function ($file) {
            return Str::after($file, "$this->relativePath/");
        }, $paths);
    }


    /**
     * @return FileManagerService
     */
    private function addRootOptions(): FileManagerService
    {
        $this->response = [
            "relativePath" => '/',
            "text"         => "Storage",
            "state"        => [
                "opened" => true
            ],
            "children"     => $this->response['directories']
        ];

        return $this;
    }


    /**
     * @return FileManagerService
     */
    private function addOptions(): FileManagerService
    {
        $this->response = collect($this->response)->map(function ($type) {
            return collect($type)->map(function ($directory) {
                return [
                    "relativePath" => (empty($this->relativePath) ? '' : $this->relativePath . '/') . $directory,
                    "text"         => $directory,
                    "icon"         => $this->getIcon($directory),
                    "ext"          => $this->getExt($directory),
                    "state"        => [
                        "opened" => false
                    ]
                ];
            })->toArray();
        })->toArray();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        $this->response['files'] = $this->paginate($this->response['files']);
        $this->response['directories'] = $this->response['directories']
            ?? ['data' => []];
        return $this->response;
    }

    /**
     * @param $directory
     * @return string
     */
    private function getIcon($directory)
    {

        return "fa fa-folder text-warning";
    }

    /**
     * @param $directory
     * @return string
     */
    private function getExt($directory)
    {
        $array = explode('.', $directory);
        return end($array);
    }

    /**
     * @param $path
     */
    private function convertToMainFile($path)
    {
        $name = basename($path, '.part');
        $newName = basename($path, '.part');
        if (Storage::disk($this->disc)->exists("chunks/{$newName}.part")) {
            if (Storage::disk($this->disc)->exists("{$this->relativePath}/{$newName}")) {
                $newName = $this->generateNewName($newName);
            }
            Storage::disk($this->disc)->move("chunks/{$name}.part", "{$this->relativePath}/{$newName}");
        }
    }

    /**
     * @param $name
     * @return string
     */
    private function generateNewName($name)
    {
        $array = explode('.', $name);
        $ext = end($array);
        $pathWithoutExt = str_replace(".{$ext}", rand(1, 1000), $name);
        return "{$pathWithoutExt}.{$ext}";
    }

    /**
     * @param $items
     * @param null $perPage
     * @param null $page
     * @param array $options
     * @return LengthAwarePaginator
     */
    public function paginate($items, $perPage = null, $page = null, $options = [])
    {
        $this->perPage = $perPage ?? $this->perPage;
        $this->page = $page ?? $this->page;
        $this->page = $this->page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($this->page, $this->perPage), $items->count(), $this->perPage, $this->page, $options);
    }

    /**
     * @return $this
     */
    private function sort()
    {
        $files = collect($this->response['files']);
        $files = $files->sort();
        $files = $this->sortBy !== 'asc' ? $files->reverse() : $files;
        $this->response['files'] = $files->toArray();

        return $this;
    }

    /**
     * @return $this
     */
    private function search()
    {
        $files = collect($this->response['files']);
        $files = $files->filter(function ($item) {
            return stripos($item, $this->searchString) !== false;
        });
        $this->response['files'] = $files->toArray();

        return $this;
    }

    /**
     * @return $this
     */
    private function filter()
    {
        if ($this->searchString) {
            $this->search();
        }
        $this->sort();

        return $this;
    }

    public function uploadFile($request)
    {
        $file = $request->file;
        $this->relativePath = $request->relativePath == '/' ? '' : $request->relativePath;

        if (!Storage::disk($this->disc)->exists("chunks")) {
            Storage::disk($this->disc)->makeDirectory("chunks");
        }
        if ($request->has('is_first') && $request->boolean('is_first')) {
            Storage::disk($this->disc)->delete("chunks/{$file->getClientOriginalName()}");
        }
        $path = Storage::disk($this->disc)->path("chunks/{$file->getClientOriginalName()}");
        File::append($path, $file->get());

        if ($request->has('is_last') && $request->boolean('is_last')) {
            $this->convertToMainFile($path);
        }

    }

    public function deleteFile($deletedDir, $requestData)
    {
        $this->configData($requestData);
        try {
            if (Storage::disk($this->disc)->exists($deletedDir)) {
                Storage::disk($this->disc)->delete($deletedDir);
                $response = $this->getData();
                return response()->json(['data' => $response, 'status' => true, 'message' => 'File deleted successfully']);
            } else {
                return response()->json(['status' => false, 'message' => 'File dose not exists']);
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
