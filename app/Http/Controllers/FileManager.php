<?php

namespace App\Http\Controllers;

use App\Rules\FileManagerFileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileManager extends Controller
{
    private $response;
    private $relativePath;
    private $disc;

    public function __construct()
    {
        $this->disc = config('filesystems.disks.local.driver');
    }

    /**
     * @return JsonResponse
     */
    public function getTree(): JsonResponse
    {
        $type = request()->post('type') ?? 'directories';
        $isRoot = (boolean)(request()->post('isRoot') ?? true);
        $this->relativePath = trim(request()->get('relativePath') ?? '/', '/');
        $response = $this->getData($type, $isRoot);
        return response()->json([$response]);
    }

    private function getData($type = 'all', $isRoot = false)
    {
        $disc = $this->disc;

        $response = $this->getFromStorage($disc, $type)->addOptions();
        if ($isRoot) $response = $response->addRootOptions();
        return $response->getResponse();

    }

    /**
     * @return JsonResponse
     */
    public function getDirectories(): JsonResponse
    {
        $this->relativePath = trim(request()->post('relativePath') ?? '/', '/');
        $disc = $this->disc;
        return response()->json(
            $this->getFromStorage($disc, 'directories')
        );
    }

    /**
     * @return JsonResponse
     */
    public function getFiles(): JsonResponse
    {
        $this->relativePath = trim(request()->post('relativePath') ?? '/', '/');
        $disc = $this->disc;
        return response()->json(
            $this->getFromStorage($disc, 'files')
        );
    }

    /**
     * @return JsonResponse
     */
    public function getFilesDirectories(): JsonResponse
    {
        $this->relativePath = trim(request()->post('relativePath') ?? '/', '/');
        $disc = $this->disc;
        return response()->json(
            $this->getFromStorage($disc)
        );
    }

    /**
     * @param string $disc
     * @param string $fetch
     * @return FileManager
     */
    private function getFromStorage(string $disc = 'local', string $fetch = ''): FileManager
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
     * @return FileManager
     */
    private function addRootOptions(): FileManager
    {
        $this->response = [
            "relativePath" => '/',
            "text"         => "Storage",
            "icon"         => "fa fa-hdd text-warning",
            "state"        => [
                "opened" => true
            ],
            "children"     => $this->response['directories']
        ];

        return $this;
    }

    /**
     * @return FileManager
     */
    private function addOptions(): FileManager
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
     * @param Request $request
     * @return array|JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function makeDir(Request $request)
    {
        $this->relativePath = $request->post('relativePath') == '/' ? '' : $request->post('relativePath');
        $directoryName = $request->post('directoryName');
        $this->validate($request, [
            'directoryName' => 'required',
            'relativePath'  => 'required',
        ]);
        try {
            if (Storage::disk($this->disc)->exists($this->relativePath . '/' . $directoryName)) {
                return response()->json(['status' => false, 'message' => 'Directory already exists']);
            } else {
                Storage::disk($this->disc)->makeDirectory($this->relativePath . '/' . $directoryName);
                $response = $this->getData();
                return response()->json(['data' => $response, 'status' => true, 'message' => 'Directory created successfully']);
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     */
    public function renameDir(Request $request)
    {
        $this->relativePath = $request->post('relativePath') == '/' ? '' : $request->post('relativePath');
        $editDirectoryName = $request->post('editDirectoryName');
        $directoryName = $request->post('directoryName');
        $this->validate($request, [
            'directoryName' => 'required',
            'relativePath'  => 'required',
        ]);
        try {
            if (Storage::disk($this->disc)->exists($editDirectoryName)) {
                Storage::disk($this->disc)->move($editDirectoryName, $directoryName);
                $response = $this->getData();
                return response()->json(['data' => $response, 'status' => true, 'message' => 'Directory renamed successfully']);
            } else {
                return response()->json(['status' => false, 'message' => 'Directory already exists']);
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     */
    public function deleteDir(Request $request)
    {

        $this->relativePath = $request->post('currentDir');
        $deletedDir = $request->post('relativePath');
        try {
            if (Storage::disk($this->disc)->exists($deletedDir)) {
                Storage::disk($this->disc)->deleteDirectory($deletedDir);
                $response = $this->getData();
                return response()->json(['data' => $response, 'status' => true, 'message' => 'Directory deleted successfully']);
            } else {
                return response()->json(['status' => false, 'message' => 'Directory dose not exists']);
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function uploadFile(Request $request):JsonResponse
    {
        $file = $request->file('file');
        $this->relativePath = $request->post('relativePath') == '/' ? '' : $request->post('relativePath');
        $this->validate($request, ['file' => new FileManagerFileUpload]);

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
        return response()->json(['uploaded' => true]);
    }

    /**
     * @param $path
     */
    private function convertToMainFile($path){
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
    private function generateNewName($name){
        $array = explode('.', $name);
        $ext = end($array);
        $pathWithoutExt=str_replace(".{$ext}",rand(1,1000),$name);
        return "{$pathWithoutExt}.{$ext}";
    }
}
