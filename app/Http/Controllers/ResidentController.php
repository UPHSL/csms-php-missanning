<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use App\Services\ResidentDeactivationService;
use App\Services\ResidentQueryService;
use App\Services\ResidentRegistrationService;
use App\Services\ResidentUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function __construct(
        private ResidentQueryService $queries,
        private ResidentRegistrationService $registration,
        private ResidentUpdateService $updates,
        private ResidentDeactivationService $deactivation,
        private ResidentRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->query('search', '');
        $search = is_string($search) ? $search : '';

        return view('residents.index', [
            'residents' => $this->queries->searchResidents($search),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('residents.form', ['resident' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resident = new Resident($this->residentInput($request) + [
            'status' => Resident::STATUS_ACTIVE,
        ]);
        $result = $this->registration->registerResident($resident);

        if (! $result->success) {
            $labels = [
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'address' => 'Address',
                'contact_number' => 'Contact Number',
                'email' => 'Email',
            ];
            $errors = [];

            foreach ($result->errors as $field) {
                $errors[$field] = 'Please check '.($labels[$field] ?? 'this field').'.';
            }

            return redirect()->route('residents.create')
                ->withErrors($errors)
                ->withInput($this->residentInput($request));
        }

        return redirect()->route('residents.index')
            ->with('success', 'Resident registered successfully.');
    }

    public function edit(int $id): View
    {
        $resident = $this->repository->findById($id);
        abort_if($resident === null, 404);

        return view('residents.form', ['resident' => $resident]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $input = $this->residentInput($request);
        $result = $this->updates->update(
            $id,
            $input['firstName'],
            $input['lastName'],
            $input['address'],
            $input['contactNumber'],
            $input['email'],
        );

        abort_if($result['notFound'], 404);

        if (! $result['success']) {
            return redirect()->route('residents.edit', $id)
                ->withErrors($result['errors'])
                ->withInput($input);
        }

        return redirect()->route('residents.index')
            ->with('success', 'Resident information updated successfully.');
    }

    public function deactivate(int $id): RedirectResponse
    {
        $result = $this->deactivation->deactivate($id);
        abort_if($result['notFound'], 404);

        return redirect()->route('residents.index')
            ->with('success', $result['alreadyInactive']
                ? 'Resident is already inactive.'
                : 'Resident deactivated successfully.');
    }

    private function residentInput(Request $request): array
    {
        $input = [];

        foreach (['firstName', 'lastName', 'address', 'contactNumber', 'email'] as $field) {
            $value = $request->input($field);
            $input[$field] = is_string($value) ? $value : '';
        }

        return $input;
    }
}
