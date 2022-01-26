<?php


namespace App\Services;


use Illuminate\Support\Facades\Storage;

class DirectoryService
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
     * @param $relativePath
     * @param $directoryName
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function makeDir($relativePath, $directoryName)
    {
        $this->relativePath = $relativePath;
        try {
            if (Storage::disk($this->disc)->exists($this->relativePath . '/' . $directoryName)) {
                return response()->json(['status' => false, 'message' => 'Directory already exists']);
            } else {
                Storage::disk($this->disc)->makeDirectory($this->relativePath . '/' . $directoryName);
                $response = (new FileManagerService())->getData();
                return response()->json(['data' => $response, 'status' => true, 'message' => 'Directory created successfully']);
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param $relativePath
     * @param $editDirectoryName
     * @param $directoryName
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function renameDir($relativePath, $editDirectoryName, $directoryName)
    {
        $this->relativePath = $relativePath;
        try {
            if (Storage::disk($this->disc)->exists($editDirectoryName)) {
                Storage::disk($this->disc)->move($editDirectoryName, $directoryName);
                $response = (new FileManagerService())->getData();
                return response()->json(['data' => $response, 'status' => true, 'message' => 'Directory renamed successfully']);
            } else {
                return response()->json(['status' => false, 'message' => 'Directory already exists']);
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param $relativePath
     * @param $editDirectoryName
     * @param $directoryName
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function deleteDir($relativePath, $deletedDir)
    {
        $this->relativePath = $relativePath;
        try {
            if (Storage::disk($this->disc)->exists($deletedDir)) {
                Storage::disk($this->disc)->deleteDirectory($deletedDir);
                $response = (new FileManagerService())->getData();
                return response()->json(['data' => $response, 'status' => true, 'message' => 'Directory deleted successfully']);
            } else {
                return response()->json(['status' => false, 'message' => 'Directory dose not exists']);
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
