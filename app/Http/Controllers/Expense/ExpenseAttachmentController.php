<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseAttachmentRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;

class ExpenseAttachmentController extends Controller
{
    public function store(StoreExpenseAttachmentRequest $request, Expense $expense): RedirectResponse
    {
        $file = $request->file('receipt');
        $path = $file->store("expense-receipts/{$expense->tenant_id}", 'public');

        $expense->attachments()->create([
            'tenant_id' => $expense->tenant_id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Receipt uploaded successfully.');
    }
}
