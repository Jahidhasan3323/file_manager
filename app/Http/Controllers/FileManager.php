<?php

namespace App\Http\Controllers;

use App\Rules\FileManagerFileUpload;
use App\Services\FileManagerService;
use http\Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileManager extends Controller
{
    private $relativePath;
    private $disc;
    private $rootPath;

    public function __construct()
    {
        $this->disc = config('filesystems.disks.local.driver');
        $this->rootPath = config('filesystems.disks.local.root');
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTree(Request $request): JsonResponse
    {

        $response = (new FileManagerService())->getResponseData($request->all());
        return response()->json([$response]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $this->validate($request, ['file' => new FileManagerFileUpload]);
        (new FileManagerService())->uploadFile($request);
        return response()->json(['uploaded' => true]);
    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     */
    public function deleteFile(Request $request)
    {
        $deletedDir = $request->post('relativePath');
        return (new FileManagerService())->deleteFile($deletedDir, $request);
    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     */
    public function changeDir(Request $request)
    {
        $this->relativePath = $request->post('currentDir') == '/' ? '' : $request->post('currentDir');
        $selectedDir = $request->post('selectedDir');
        $targetDir = $request->post('targetDir');
        $changeType = $request->post('changeType');
        $changeFileType = $request->post('changeFileType');
        try {
            if (!Storage::disk($this->disc)->exists($this->relativePath . '/' . $targetDir . '/' . $selectedDir)) {
                switch ([$changeFileType, $changeType]) {
                    case ['directory', 'copy'] :
                    {
                        $this->copyDirectory($targetDir, $selectedDir);
                        break;
                    }
                    case ['directory', 'move'] :
                    {
                        $this->moveDirectory($targetDir, $selectedDir);
                        break;
                    }
                    case ['file', 'copy'] :
                    {
                        $this->copyFile($targetDir, $selectedDir);
                        break;
                    }
                    case ['file', 'move'] :
                    {
                        $this->moveFile($targetDir, $selectedDir);
                        break;
                    }
                }
                $response = [];
                return response()->json(['data' => $response, 'status' => true, 'message' => "{$changeFileType} {$changeType} successfully"]);
            } else {
                return response()->json(['status' => false, 'message' => "{$changeFileType} already exists"]);
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param $targetDir
     */
    private function copyDirectory($targetDir, $selectedDir)
    {
        $selectedDirectoryArray = explode('/', $selectedDir);
        $selectedDirectory = end($selectedDirectoryArray);
        $targetDir = "{$this->relativePath}/{$targetDir}/{$selectedDirectory}";
        $selectedDir = ($this->relativePath == '/' ? $this->relativePath . '/' : '') . $selectedDir;
        if (!Storage::disk($this->disc)->exists($targetDir)) {
            Storage::disk($this->disc)->makeDirectory($targetDir);
            File::copyDirectory($selectedDir, $targetDir);
        } else {
            throw new \ErrorException('Directory already exist');
        }

    }

    /**
     * @param $targetDir
     * @param $selectedDir
     */
    private function moveDirectory($targetDir, $selectedDir)
    {
        $selectedDirectoryArray = explode('/', $selectedDir);
        $selectedDirectory = end($selectedDirectoryArray);
        $targetDir = "{$this->relativePath}/{$targetDir}/{$selectedDirectory}";
        Storage::disk($this->disc)->move($selectedDir, $targetDir);
    }

    /**
     * @param $targetDir
     * @param $selectedDir
     */
    private function copyFile($targetDir, $selectedDir)
    {
        $selectedFileArray = explode('/', $selectedDir);
        $selectedFile = end($selectedFileArray);
        $selectedDir = ($this->relativePath == '/' ? $this->relativePath . '/' : '') . $selectedDir;
        $targetDir = "{$this->relativePath}/{$targetDir}/{$selectedFile}";
        Storage::disk($this->disc)->copy($selectedDir, $targetDir);
    }

    /**
     * @param $targetDir
     * @param $selectedDir
     */
    private function moveFile($targetDir, $selectedDir)
    {
        $selectedFileArray = explode('/', $selectedDir);
        $selectedFile = end($selectedFileArray);
        $selectedDir = ($this->relativePath == '/' ? $this->relativePath . '/' : '') . $selectedDir;
        $targetDir = "{$this->relativePath}/{$targetDir}/{$selectedFile}";
        Storage::disk($this->disc)->move($selectedDir, $targetDir);
    }


}
