<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $items = Item::all();

        return $this->success($items, 'Data retrieved successfully');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attr = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'required|string|max:255|unique:items,kode_barang',
            'harga_beli' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'stok' => 'required|numeric'
        ]);

        $itm = Item::create([
            'user_id' => Auth::id(),
            'nama_barang' => $attr['nama_barang'],
            'kode_barang' => $attr['kode_barang'],
            'harga_beli' => $attr['harga_beli'],
            'harga_jual' => $attr['harga_jual'],
            'stok' => $attr['stok'],
        ]);

        return $this->success($itm, 'Data inserted successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        $itm = Item::findOrFail($item->id);

        return $this->success($itm, 'Data with that id retrieved successfully');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function edit(Item $item)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Item $item)
    {
        $attr = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'required|string|max:255|unique:items,kode_barang,' . $item->id,
            'harga_beli' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'stok' => 'required|numeric'
        ]);

        $itm = Item::firstOrFail('id', $item->id)->update([
            'nama_barang' => $attr['nama_barang'],
            'kode_barang' => $attr['kode_barang'],
            'harga_beli' => $attr['harga_beli'],
            'harga_jual' => $attr['harga_jual'],
            'stok' => $attr['stok'],
        ]);

        $attr = Arr::prepend($attr, Auth::id(), 'user_id');

        return $this->success($attr, 'Data updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy(Item $item)
    {
        $itm = Item::firstOrFail('id', $item->id)->delete();

        return $this->success(null, 'Data deleted successfully');
    }
}
