<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Order;
use App\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function orderPost(Request $request)
    {
        $this->validate($request, [
            'id_pendaftar' => 'required|integer',
            'id_kursus' => 'required|integer',
            'biaya_kursus' => 'required|integer',
            'diskon_kursus' => 'required|integer',
        ]);

        $pendaftarId = Auth::id();

        $harga_kursus = $request->biaya_kursus;
        $diskon_kursus = $request->diskon_kursus;
        $diskon = $harga_kursus * ($diskon_kursus / 100);

        $check_order = Order::where('id_pendaftar', $pendaftarId)
            ->where('status_kursus', 'PROCESS')
            ->count();

        if ($check_order == 0) {
            $order = Order::create([
                'id_pendaftar' => $pendaftarId,
                'total_tagihan' => $request->biaya_kursus - $diskon,
                'status_kursus' => 'PROCESS',
            ]);
            $orderId = $order->id;
        } else {
            $orderId = Order::where('id_pendaftar', $pendaftarId)
                ->where('status_kursus', 'PROCESS')
                ->first()
                ->id;
            Order::where('id', $orderId)->increment('total_tagihan', $request->biaya_kursus - $diskon);
        }

        OrderDetail::create([
            'id_order' => $orderId,
            'id_pendaftar' => $request->id_pendaftar,
            'id_kursus' => $request->id_kursus,
            'biaya_kursus' => $request->biaya_kursus - $diskon,
            'status' => 'PROCESS',
        ]);

        $order_k = OrderDetail::with('kursus')
            ->where('id_pendaftar', $pendaftarId)
            ->orderBy('created_at', 'desc')
            ->take(1)
            ->get();

        return redirect()->route('order.success')->with([
            'order' => $order_k
        ]);
    }

    public function view()
    {
        $pendaftarId = Auth::id();
        $order_kursus = OrderDetail::with(['pendaftar', 'kursus'])
            ->where('id_pendaftar', $pendaftarId)
            ->where(function ($query) {
                $query->where('status', 'PROCESS')
                    ->orWhere('status', 'CANCEL');
            })
            ->withCount('kursus')
            ->orderBy('created_at', 'ASC')
            ->get();

        $total_tagihan = OrderDetail::where('id_pendaftar', $pendaftarId)
            ->where('status', 'PROCESS')
            ->sum('biaya_kursus');

        $order = Order::where('id_pendaftar', $pendaftarId)
            ->where(function ($query) {
                $query->where('status_kursus', 'PENDING')
                    ->orWhere('status_kursus', 'FAILED');
            })
            ->get();
        $order_status = Order::where('id_pendaftar', $pendaftarId)
            ->where(function ($query) {
                $query->where('status_kursus', 'PENDING')
                    ->orWhere('status_kursus', 'FAILED');
            })
            ->count();
        $kursus_state = OrderDetail::with(['kursus'])->where('status', 'PENDING')->where('id_pendaftar', $pendaftarId)->get();
        return view('web.web_order_cart', compact('order_kursus', 'total_tagihan', 'order', 'order_status', 'kursus_state'));
    }

    public function updateToPending(Request $request)
    {
        $order = OrderDetail::findOrFail($request->order_id);
        $order->status = $request->status;
        $order->save();
        // hitung total harga
        $tot_tagihan = OrderDetail::where('id_pendaftar', $request->id_pendaftar)
            ->where('status', 'PROCESS')
            ->sum('biaya_kursus');

        Order::find($request->order_fk)->update(['total_tagihan' => $tot_tagihan]);

        return response()->json([
            'message' => 'Bimbel ' . $request->nama_kursus . ' berhasil di update status.',
            'totalTagihan' => $tot_tagihan
        ]);
    }

    public function updateToDelete($id)
    {

        $order_detail = OrderDetail::findOrFail($id);
        $order_detail->forceDelete();

        $order = Order::find($order_detail->id_order);
        // $decrement = $order->total_tagihan - $order_detail->biaya_kursus;
        $tot_tagihan = OrderDetail::where('id_pendaftar', $order_detail->id_pendaftar)
            ->where('status', 'PROCESS')
            ->sum('biaya_kursus');
        $order->update(['total_tagihan' => $tot_tagihan]);

        return response()->json([
            'message' => 'Bimbel berhasil di cancel.',
            'totalTagihan' => $order->total_tagihan
        ]);
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'fileTransfer' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $pendaftarId = Auth::id();
        $order = Order::where('id_pendaftar', $pendaftarId)
            ->where('status_kursus', 'PROCESS')
            ->first();

        if ($order->upload_bukti == NULL) {
            $fileName = "buktibayar-" . time() . '.' . request()->fileTransfer->getClientOriginalExtension();
            $request->fileTransfer->storeAs('public/uploads/bukti_pembayaran', $fileName);
            $order->upload_bukti = $fileName;
            $order->status_kursus = 'PENDING';
            $order->save();

            OrderDetail::where('id_order', $order->id)
                ->where('id_pendaftar', $pendaftarId)
                ->where('status', 'PROCESS')
                ->update(['status' => 'PENDING']);
            OrderDetail::where('id_order', $order->id)
                ->where('id_pendaftar', $pendaftarId)
                ->where('status', 'CANCEL')
                ->forceDelete();
        }

        return redirect('order/cart');
    }

    public function updateFile(Request $request)
    {
        $request->validate([
            'fileTransfer' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $pendaftarId = Auth::id();
        $data = Order::where('id_pendaftar', $pendaftarId)->where('status_kursus', 'FAILED')->first();

        if ($request->hasFile('fileTransfer')) {
            Storage::disk('local')->delete('public/uploads/bukti_pembayaran/' . $data->upload_bukti);

            $fileName = "buktibayar-" . time() . '.' . request()->fileTransfer->getClientOriginalExtension();
            $request->fileTransfer->storeAs('public/uploads/bukti_pembayaran', $fileName);
            $data->upload_bukti = $fileName;
            $data->status_kursus = 'PENDING';
            $data->save();
        }

        return redirect('order/cart');
    }

    public function deleteCheckout($id)
    {

        $order = Order::findOrFail($id);
        Storage::disk('local')->delete('public/uploads/bukti_pembayaran/' . $order->upload_bukti);
        $order->forceDelete();

        return response()->json([
            'message' => 'Konfirmasi berhasil dibatalkan.'
        ]);
    }

    public function success()
    {
        $data = "Bimble | Halaman Sukses";
        return view('web.web_success', ['title' => $data]);
    }
}
