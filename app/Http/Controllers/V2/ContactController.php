<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public const TYPE_LABELS = [
        'customer' => 'Pelanggan',
        'supplier' => 'Pemasok',
        'other' => 'Lainnya',
    ];

    public function index(Request $request): View
    {
        $contacts = Contact::query()
            ->when($request->string('type')->value(), fn ($q, $type) => $q->where('type', $type))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('v2.contacts.index', [
            'contacts' => $contacts,
            'typeLabels' => self::TYPE_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('v2.contacts.form', [
            'contact' => new Contact(['type' => 'customer', 'is_active' => true]),
            'typeLabels' => self::TYPE_LABELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Contact::create($this->validateData($request) + ['company_id' => Company::query()->value('id')]);

        return redirect()->route('v2.contacts')->with('status', 'Kontak berhasil ditambahkan.');
    }

    public function edit(Contact $contact): View
    {
        return view('v2.contacts.form', [
            'contact' => $contact,
            'typeLabels' => self::TYPE_LABELS,
        ]);
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $contact->update($this->validateData($request));

        return redirect()->route('v2.contacts')->with('status', 'Kontak berhasil diperbarui.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('v2.contacts')->with('status', 'Kontak dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:customer,supplier,other'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
