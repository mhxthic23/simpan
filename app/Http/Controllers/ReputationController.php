<?php

namespace App\Http\Controllers;

use App\Models\TokenReputasi;
use App\Services\ReputationLedgerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReputationController extends Controller
{
    public function index(Request $request, ReputationLedgerService $ledger): View
    {
        $entries = TokenReputasi::query()->where('user_id', $request->user()->id)->latest('created_at')->paginate(30);
        $balance = $ledger->balance($request->user());
        return view('reputation.index', compact('entries', 'balance'));
    }
}
