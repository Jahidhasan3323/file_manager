<?php

namespace App\Http\Controllers;

use App\Services\DirectoryService;
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
    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function makeDir(Request $request)
    {
        $relativePath = $request->post('relativePath') == '/' ? '' : $request->post('relativePath');
        $directoryName = $request->post('directoryName');
        $this->validate($request, [
            'directoryName' => 'required',
            'relativePath'  => 'required',
        ]);
        return (new DirectoryService())->makeDir($relativePath, $directoryName);

    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     */
    public function renameDir(Request $request)
    {
        $relativePath = $request->post('relativePath') == '/' ? '' : $request->post('relativePath');
        $editDirectoryName = $request->post('editDirectoryName');
        $directoryName = $request->post('directoryName');
        $this->validate($request, [
            'directoryName' => 'required',
            'relativePath'  => 'required',
        ]);

        return (new DirectoryService())->renameDir($relativePath,$editDirectoryName, $directoryName);

    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     */
    public function deleteDir(Request $request)
    {
        $relativePath = $request->post('currentDir');
        $deletedDir = $request->post('relativePath');
        return (new DirectoryService())->deleteDir($relativePath, $deletedDir);
    }
}
