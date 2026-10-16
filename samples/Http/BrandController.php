<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BrandController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $brand = $user->brand()->with(['aliases', 'competitors'])->first();
        $limit = $user->subscription->limits()['competitors'];

        return view('brand.edit', compact('brand', 'limit'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'aliases' => ['nullable', 'string', 'max:2000'],
            'competitors' => ['nullable', 'string', 'max:2000'],
        ]);

        $aliases = $this->lines($data['aliases'] ?? '');
        $competitors = $this->lines($data['competitors'] ?? '');

        // The plan decides how many competitors a run checks (Starter 3, Agency 5).
        // The form only submits the editable slots, so a longer list is rejected.
        // Extras saved earlier (e.g. before a downgrade) are kept but stay unchecked.
        $limits = $user->subscription->limits();
        $limit = (int) $limits['competitors'];
        if (count($competitors) > $limit) {
            $max = max(array_map(fn ($plan) => (int) $plan['competitors'], config('askmention.plans')));
            $message = "{$limits['name']} checks up to {$limit} competitors.";
            if ($limit < $max) {
                $message .= " Agency checks up to {$max}.";
            }

            throw ValidationException::withMessages(['competitors' => $message]);
        }

        $brand = Brand::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['name' => $data['name']],
        );

        $brand->aliases()->delete();
        foreach ($aliases as $alias) {
            $brand->aliases()->create(['alias' => $alias]);
        }

        $this->syncCompetitors($brand, $competitors, $limit);

        return redirect()->route('brand.edit')->with('status', 'Brand saved.');
    }

    /**
     * Editable slots = the first $limit competitors. Rows with an unchanged
     * name keep their id (mention history), extras beyond $limit are kept
     * as they are, after the editable ones.
     *
     * @param  list<string>  $names
     */
    private function syncCompetitors(Brand $brand, array $names, int $limit): void
    {
        $existing = $brand->competitors()->get();
        $editable = $existing->take($limit)->values();
        $extras = $existing->slice($limit)->values();

        // A name that already sits in a locked extra slot is not duplicated.
        $extraNames = $extras->pluck('name')->all();
        $names = array_values(array_filter($names, fn ($name) => ! in_array($name, $extraNames, true)));

        $keep = [];
        foreach ($names as $position => $name) {
            $row = $editable->first(fn ($competitor) => $competitor->name === $name && ! in_array($competitor->id, $keep, true));
            if ($row) {
                $row->update(['position' => $position]);
                $keep[] = $row->id;
            } else {
                $keep[] = $brand->competitors()->create(['name' => $name, 'position' => $position])->id;
            }
        }

        $editable->whereNotIn('id', $keep)->each->delete();

        foreach ($extras as $offset => $row) {
            $row->update(['position' => $limit + $offset]);
        }
    }

    /**
     * @return list<string>
     */
    private function lines(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $clean = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '' && ! in_array($line, $clean, true)) {
                $clean[] = $line;
            }
        }

        return array_slice($clean, 0, 20);
    }
}
