<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Dropfile;

class DropfileController extends Controller
{
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

                    // Upload ke Dropbox
                    Storage::disk('dropbox')->put('public/upload/' . $newName, $fileContent);

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
            // Mengambil Dropbox client melalui disk
            $dropboxClient = Storage::disk('dropbox')->getAdapter()->getClient();

            // Mengambil link berbagi dari Dropbox
            $link = $dropboxClient->createSharedLinkWithSettings('public/upload/' . $fileTitle);

            // Menyiapkan URL dengan parameter raw
            $rawUrl = $link['url'] . '?raw=1';

            // Menyajikan file sebagai response
            return response()->redirectTo($rawUrl);
        } catch (\Exception $e) {
            return abort(404);
        }
    }

    public function download($fileTitle)
    {
        try {
            // Mengambil Dropbox client melalui disk
            $dropboxClient = Storage::disk('dropbox')->getAdapter()->getClient();

            // Mendapatkan file dari Dropbox melalui shared link
            $link = $dropboxClient->createSharedLinkWithSettings('public/upload/' . $fileTitle);
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
            Storage::disk('dropbox')->delete('public/upload/' . $file->file_title);

            // Menghapus metadata file dari database
            $file->delete();

            return redirect('drop');
        } catch (\Exception $e) {
            return abort(404);
        }
    }
}
