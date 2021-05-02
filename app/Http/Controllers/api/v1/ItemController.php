<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    // Using ApiResponser's trait.
    use ApiResponser;

    public function index()
    {
        // Getting all items.
        $items = Item::where('user_id', Auth::id())->get();

        // Returning success API reseponse.
        return $this->success($items, 'All items was retrieved successfully.');
    }

    public function store(Request $request)
    {
        // Validating incoming request.
        $attr = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'required|string|max:255|unique:items,kode_barang',
            'harga_beli' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'stok' => 'required|numeric'
        ]);

        // Creating new item.
        $item = Item::create([
            'user_id' => Auth::id(),
            'nama_barang' => $attr['nama_barang'],
            'kode_barang' => $attr['kode_barang'],
            'harga_beli' => $attr['harga_beli'],
            'harga_jual' => $attr['harga_jual'],
            'stok' => $attr['stok'],
        ]);

        // Returning success API response.
        return $this->success($item, 'Item was created successfully.');
    }

    public function show(Item $item)
    {
        // Validating selected item for authenticated user.
        if ($item->user_id != Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Getting selected item.
        $currentItem = Item::findOrFail($item->id);

        // Returning success API response.
        return $this->success($currentItem, 'Item with that id was retrieved successfully.');
    }

    public function update(Request $request, Item $item)
    {
        // Validating selected item for authenticated user.
        if ($item->user_id !== Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Validating incoming request.
        $attr = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'required|string|max:255|unique:items,kode_barang,' . $item->id,
            'harga_beli' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'stok' => 'required|numeric'
        ]);

        // Updating selected item.
        $updateItem = Item::where('id', $item->id)->update([
            'nama_barang' => $attr['nama_barang'],
            'kode_barang' => $attr['kode_barang'],
            'harga_beli' => $attr['harga_beli'],
            'harga_jual' => $attr['harga_jual'],
            'stok' => $attr['stok'],
        ]);

        // Returning success API response.
        return $this->success($attr, 'Item was updated successfully.');
    }

    public function destroy(Item $item)
    {
        // Validating selected item for authenticated user.
        if ($item->user_id !== Auth::id()) {
            return $this->error('Access was not allowed.', 403);
        }

        // Deleting selected item.
        $deleteItem = Item::where('id', $item->id)->delete();

        // Returning success API response.
        return $this->success(null, 'Item was deleted successfully.');
    }
}
