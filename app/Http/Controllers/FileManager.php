<?php

namespace App\Http\Controllers;

use App\Rules\FileManagerFileUpload;
use App\Services\FileManagerService;
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
     * @param Request $request
     * @return array|JsonResponse
     */
    public function deleteFile(Request $request)
    {
        $this->relativePath = $request->post('currentDir');
        $deletedDir = $request->post('relativePath');
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
            if (Storage::disk($this->disc)->exists($targetDir)) {
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
                $response = $this->getData();
                return response()->json(['data' => $response, 'status' => true, 'message' => "{$changeFileType} {$changeType} successfully"]);
            } else {
                return response()->json(['status' => false, 'message' => "{$changeFileType} dose not exists"]);
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
//        TODO::copy directory is already exist check
//        dd($this->rootPath.'/'.$targetDir);
        if (!Storage::disk($this->disc)->exists($targetDir.'/'.$selectedDir)) {
            Storage::disk($this->disc)->makeDirectory($targetDir.'/'.$selectedDir);
            File::copyDirectory($this->rootPath . '/' . $selectedDir, $this->rootPath . '/' . $targetDir .'/'. $selectedDir);
        }else{
            return response()->json(['status' => false, 'message' => "directory already exists"]);
        }

    }

    /**
     * @param $targetDir
     * @param $selectedDir
     */
    private function moveDirectory($targetDir, $selectedDir)
    {
        Storage::disk($this->disc)->move($selectedDir, $targetDir . '/' . $selectedDir);
    }

    /**
     * @param $targetDir
     * @param $selectedDir
     */
    private function copyFile($targetDir, $selectedDir)
    {
        Storage::disk($this->disc)->copy($this->relativePath . '/' . $selectedDir, $targetDir . '/' . $selectedDir);
    }

    /**
     * @param $targetDir
     * @param $selectedDir
     */
    private function moveFile($targetDir, $selectedDir)
    {
        Storage::disk($this->disc)->move($this->relativePath . '/' . $selectedDir, $targetDir . '/' . $selectedDir);
    }


}
