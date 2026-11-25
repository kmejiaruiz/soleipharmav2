<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carousel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CarouselController extends Controller
{
    public function index()
    {
        $carousels = Carousel::orderBy('order')->orderByDesc('id')->get()
            ->map(function ($item) {
                $item->image_url = $item->image
                    ? Storage::disk('public')->url('carousel/' . $item->image)
                    : null;
                return $item;
            });

        return Inertia::render('Admin/Carousel/Index', [
            'carousels' => $carousels,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'required|image|max:5120',
            'active'      => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('carousel', 'public');
            $validated['image'] = basename($path);
        }

        $validated['active'] = $request->boolean('active', true);

        Carousel::create($validated);

        return redirect()->route('carousel.index')->with('success', 'Banner agregado al Carousel correctamente.');
    }

    public function update(Request $request, Carousel $carousel)
    {
        $validated = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:5120',
            'active'      => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($carousel->image && Storage::disk('public')->exists('carousel/' . $carousel->image)) {
                Storage::disk('public')->delete('carousel/' . $carousel->image);
            }
            $path = $request->file('image')->store('carousel', 'public');
            $validated['image'] = basename($path);
        } else {
            unset($validated['image']);
        }

        if ($request->has('active')) {
            $validated['active'] = $request->boolean('active');
        }

        $carousel->update($validated);

        return redirect()->route('carousel.index')->with('success', 'Banner actualizado correctamente.');
    }

    public function toggle(Carousel $carousel)
    {
        $carousel->update(['active' => !$carousel->active]);

        return redirect()->route('carousel.index')->with('success', 'Estado del banner actualizado.');
    }

    public function destroy(Carousel $carousel)
    {
        if ($carousel->image && Storage::disk('public')->exists('carousel/' . $carousel->image)) {
            Storage::disk('public')->delete('carousel/' . $carousel->image);
        }

        $carousel->delete();

        return redirect()->route('carousel.index')->with('success', 'Banner eliminado del Carousel.');
    }
}