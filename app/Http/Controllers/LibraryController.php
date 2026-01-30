<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\Shelf;
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    /**
     * Menampilkan halaman Library user.
     */
    public function index()
    {
        // PERBAIKAN: Ambil game HANYA yang dimiliki user melalui relasi games()
        // Ini memastikan game yang belum dibeli (hanya di Store) tidak muncul.
        $user = Auth::user();
        $ownedGames = $user->games()->get();
        
        // Ambil Rak/Collection milik user yang sedang login
        $userShelves = Shelf::where('user_id', Auth::id())->with('games')->get();

        return view('library', compact('ownedGames', 'userShelves'));
    }

    /**
     * Membuat Shelf/Collection baru.
     */
    public function storeShelf(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mode' => 'required|in:manual,dynamic',
        ]);

        $shelf = Shelf::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'type' => $request->mode,
            'criteria' => $request->genre, // Disimpan jika mode dynamic
        ]);
    
        if ($request->mode === 'manual') {
            if ($request->has('selected_games')) {
                // Attach game yang dipilih ke shelf (pastikan game ini milik user)
                $ownedGameIds = Auth::user()->games()->whereIn('game_id', $request->selected_games)->pluck('game_id');
                $shelf->games()->syncWithoutDetaching($ownedGameIds);
            }
        } 
        elseif ($request->mode === 'dynamic') {
            // Cari game yang dimiliki user yang sesuai dengan genre dan attach
            $ownedGameIds = Auth::user()->games()->where('genre', $request->genre)->pluck('game_id');
            $shelf->games()->syncWithoutDetaching($ownedGameIds);
        }
    
    
        return redirect()->back()->with('success', 'Shelf berhasil dibuat!');
    }

    /**
     * Download dummy installer for the game.
     */
    public function download(Game $game)
    {
        // Check local ownership (optional check, but good practice)
        // $ownsGame = Auth::user()->games()->where('game_id', $game->id)->exists();
        // if (!$ownsGame) abort(403, 'You do not own this game.');

        $content = "Installer for " . $game->title . "\n\nThis is a dummy file simulating the installation of the game.\nThank you for downloading from Kukus Store!";
        $filename = "Install-" . preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $game->title)) . ".exe";
        
        // For safety, we append .txt so it doesn't actually try to run, or we can force it as text/plain but name it .exe
        // User asked for dummy file, let's keep it simple as text file named .bat or .txt to avoid browser blocking
        // But user said "sistem select dummy file based on game name"
        
        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename);
    }
}