<?php

namespace App\Http\Controllers;

use App\Services\FileManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\FileManager;

class DirectoryController extends Controller
{
    private $disc;

    /**
     * DirectoryController constructor.
     */
    public function __construct()
    {
        $this->disc = config('filesystems.disks.local.driver');
        $this->rootPath = config('filesystems.disks.local.root');
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
                $response = (new FileManagerService())->getData();
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
}
