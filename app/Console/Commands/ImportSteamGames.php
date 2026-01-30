<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Game;
use Illuminate\Support\Facades\Log;

class ImportSteamGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:steam-games';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import popular games data from Steam Store API';

    /**
     * List of Steam App IDs to import.
     * Selected popular games for demo purposes.
     */
    protected $appIds = [
        730,     // Counter-Strike 2
        570,     // Dota 2
        578080,  // PUBG: BATTLEGROUNDS
        1172470, // Apex Legends
        271590,  // Grand Theft Auto V
        1091500, // Cyberpunk 2077
        1245620, // Elden Ring
        1086940, // Baldur's Gate 3
        230410,  // Warframe
        1085660, // Destiny 2
        1172620, // Sea of Thieves
        1623730, // Palworld
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Steam Game Import...');

        // 1. Fetch Dynamic App IDs with Metadata
        $dynamicGames = $this->fetchDynamicAppIds(); // Returns [['id' => 123, 'is_featured' => true], ...]
        
        // 2. Prepare Curated List Metadata (Default to Featured)
        $curatedGames = [];
        foreach ($this->appIds as $id) {
            $curatedGames[$id] = ['id' => $id, 'is_featured' => true];
        }

        // 3. Merge Lists (Dynamic overwrites Curated if duplicate ID exists, which is fine)
        // Using ID as key to deduplicate
        $allGames = $curatedGames; 
        foreach ($dynamicGames as $game) {
            $allGames[$game['id']] = $game;
        }
        
        // Limit to 100 games
        $allGames = array_slice($allGames, 0, 100);
        
        $total = count($allGames);
        $this->info("Found {$total} unique games to import.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($allGames as $gameData) {
            $id = $gameData['id'];
            
            try {
                // Disable SSL verification
                $response = Http::withoutVerifying()->get("https://store.steampowered.com/api/appdetails?appids={$id}&cc=id"); 

                if ($response->successful()) {
                    $json = $response->json();
                    
                    if (isset($json[$id]['success']) && $json[$id]['success']) {
                        $pdata = $json[$id]['data'];
                        $this->importGame($pdata, $gameData); // Pass metadata
                    }
                } 

                usleep(1500000); // 1.5 seconds

            } catch (\Exception $e) {
                Log::error("Steam Import Error ID {$id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Steam Game Import Completed!');
    }

    private function fetchDynamicAppIds()
    {
        $this->info("Fetching Featured Categories...");
        $games = []; // Format: [['id' => 123, 'is_featured' => true], ...]
        
        try {
            $response = Http::withoutVerifying()->get("https://store.steampowered.com/api/featuredcategories");
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Categories to treat as FEATURED
                $featuredGroups = ['cat_spotlight', '0', '1', '2', '3']; // Spotlights are usually numbered or key '0'
                
                // Categories
                 foreach ($data as $key => $group) {
                     if (!isset($group['items'])) continue;
                     
                     // Determine if this group is considered "Featured" (Spotlight or Top Sellers)
                     // Note: 'top_sellers' isn't explicitly in the root keys usually, it's 'cat_topsellers' or similar ID inside structure
                     // But we can check keys or 'id' field of group
                     $isFeatured = false;
                     
                     if (in_array($key, $featuredGroups) || 
                         (isset($group['id']) && in_array($group['id'], ['cat_spotlight', 'cat_topsellers']))) {
                         $isFeatured = true;
                     }

                     foreach ($group['items'] as $item) {
                        $id = null;
                        if (isset($item['id'])) {
                            $id = $item['id'];
                        } elseif (isset($item['url'])) {
                            if (preg_match('/app\/(\d+)/', $item['url'], $matches)) {
                                $id = intval($matches[1]);
                            }
                        }

                        if ($id) {
                            // If game already exists in list, we can update featured status if true
                            if (isset($games[$id])) {
                                if ($isFeatured) $games[$id]['is_featured'] = true;
                            } else {
                                $games[$id] = ['id' => $id, 'is_featured' => $isFeatured];
                            }
                        }
                     }
                 }
            }
        } catch (\Exception $e) {
            $this->error("Failed to fetch featured categories: " . $e->getMessage());
        }
        
        return array_values($games);
    }

    private function importGame($data, $metadata)
    {
        // 1. Mapping Basic Info
        $title = $data['name'] ?? 'Unknown Game';
        
        // Description
        $description = $data['short_description'] ?? strip_tags($data['detailed_description'] ?? '');

        // 2. Price Handling and Discount
        $price = 0;
        $discountPercent = 0;

        if (isset($data['price_overview'])) {
            // Steam 'final' is in cents.
            $price = $data['price_overview']['final'] / 100;
            $discountPercent = $data['price_overview']['discount_percent'] ?? 0;
        } elseif (isset($data['is_free']) && $data['is_free']) {
            $price = 0;
            $discountPercent = 0;
        }

        // 3. Genre
        $genre = 'Action';
        if (isset($data['genres']) && count($data['genres']) > 0) {
            $genre = $data['genres'][0]['description'];
        }

        // 4. Screenshots
        $screenshots = [];
        if (isset($data['screenshots'])) {
            foreach (array_slice($data['screenshots'], 0, 5) as $shot) {
                $screenshots[] = $shot['path_full'];
            }
        }

        // 5. Trailer
        $trailerUrl = null;
        if (isset($data['movies']) && count($data['movies']) > 0) {
            $trailerUrl = $data['movies'][0]['mp4']['480'] ?? $data['movies'][0]['mp4']['max'] ?? null;
        }

        // 6. DB Insert/Update
        Game::updateOrCreate(
            ['title' => $title], 
            [
                'description'   => $description,
                'price'         => $price,
                'genre'         => $genre,
                'publisher'     => $data['publishers'][0] ?? 'Steam',
                'release_date'  => $this->parseDate($data['release_date']['date'] ?? now()),
                'cover_image'   => $data['header_image'] ?? null,
                'screenshots'   => $screenshots,
                'trailer_url'   => $trailerUrl,
                'is_approved'   => true,
                'is_featured'   => $metadata['is_featured'] ?? false,
                'discount_percent' => $discountPercent
            ]
        );

        // $this->line("Imported: {$title}"); // Quiet mode
    }

    private function parseDate($dateString)
    {
        try {
            // Steam dates can be "25 Dec, 2023" or "Coming Soon"
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return now();
        }
    }
}
