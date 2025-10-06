<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class R2FileController extends Controller
{
    public function previewFile(Request $request, $subFolder, $fileName) {
        try {
            $filePath = 'uploads/'. $subFolder .'/'. $fileName;
            $fileContents = Storage::disk('r2')->get($filePath);
            if($fileContents) {
                $headers = [
                    'Content-Type' => Storage::disk('r2')->mimeType($filePath),
                    'Content-Disposition' => (new ResponseHeaderBag())->makeDisposition(
                        ResponseHeaderBag::DISPOSITION_INLINE,
                        basename($filePath)
                    ),
                ];
                return response($fileContents, Response::HTTP_OK, $headers);
            } else {
                abort(404);
            }
        } catch(\Exception $e) {
            abort(404);
        }
    }
}
