<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Dropfile;
use Spatie\Dropbox\Client as DropboxClient;

class DropfileController extends Controller
{
    private $dropboxClient;

    public function __construct()
    {
        // Menggunakan DropboxClient secara langsung
        $this->dropboxClient = new DropboxClient(env('DROPBOX_ACCESS_TOKEN'));
    }

    public function index()
    {
        $files = Dropfile::all();
        return view('pages.drop-index', compact('files'));
    }

    public function store(Request $request)
    {
        try {
            if ($request->hasFile('file')) {
                $files = $request->file('file');

                foreach ($files as $file) {
                    $fileExtension = $file->getClientOriginalExtension();
                    $mimeType = $file->getClientMimeType();
                    $fileSize = $file->getSize();
                    $newName = uniqid() . '.' . $fileExtension;

                    // Membaca konten file
                    $fileContent = file_get_contents($file->getRealPath());

                    // Upload ke Dropbox menggunakan Dropbox API
                    $this->dropboxClient->upload('/public/upload/' . $newName, $fileContent, 'overwrite');

                    // Simpan metadata file ke database
                    Dropfile::create([
                        'file_title' => $newName,
                        'file_type' => $mimeType,
                        'file_size' => $fileSize,
                    ]);
                }

                return redirect('drop');
            }
        } catch (\Exception $e) {
            return "Message: {$e->getMessage()}";
        }
    }

    public function show($fileTitle)
    {
        try {
            // Mendapatkan URL berbagi untuk file
            $link = $this->dropboxClient->createSharedLinkWithSettings('/public/upload/' . $fileTitle);

            // Mengarahkan ke file dengan parameter raw
            return redirect($link['url'] . '?raw=1');
        } catch (\Exception $e) {
            return abort(404);
        }
    }

    public function download($fileTitle)
    {
        try {
            // Mendapatkan URL berbagi untuk file
            $link = $this->dropboxClient->createSharedLinkWithSettings('/public/upload/' . $fileTitle);
            $rawUrl = $link['url'] . '?raw=1';

            // Mengunduh file menggunakan URL raw
            return response()->download($rawUrl);
        } catch (\Exception $e) {
            return abort(404);
        }
    }

    public function destroy($id)
    {
        try {
            $file = Dropfile::findOrFail($id);

            // Menghapus file dari Dropbox
            $this->dropboxClient->delete('/public/upload/' . $file->file_title);

            // Menghapus metadata file dari database
            $file->delete();

            return redirect('drop');
        } catch (\Exception $e) {
            return abort(404);
        }
    }
}
