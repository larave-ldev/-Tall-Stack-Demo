<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierAddresses;
use App\Models\SupplierContacts;
use App\Promodata\Suppliers;
use Carbon\Carbon;
use Exception;
use JetBrains\PhpStorm\NoReturn;

class SuppliersService extends Suppliers
{
    private array $supplierIdsList;
    private array $addressesDataToInsert = [];
    private array $contactsDataToInsert = [];

    private Suppliers $suppliers;

    public function __construct(Suppliers $suppliers)
    {
        parent::__construct();
        $this->supplierIdsList = config('promodata.supplier_ids_list');
        $this->suppliers = $suppliers;
    }

    /**
     * @throws Exception
     */
    public function getSuppliers(): void
    {
        $page = 1;
        do {
            $result = $this->suppliers->get(['page' => $page]);
            self::processSuppliersData($result['data']);
            self::bulkInsertData();
            $page++;
        } while ($page <= $result['total_pages']);
    }

    /**
     * @param  string  $fromDate entered from command line
     *
     * @throws Exception
     */
    public function updateSuppliers(string $fromDate): void
    {
        $page = 1;
        do {
            $result = $this->suppliers->get([['page' => $page]]);
            self::processUpdateSuppliersData($result['data'], $fromDate);
            self::bulkInsertData();
            $page++;
        } while ($page <= $result['total_pages']);
    }

    /**
     * This function will ignore the suppliers
     *
     * @throws Exception
     */
    #[NoReturn]
    public function ignoreSuppliers(): void
    {
        $count = count($this->supplierIdsList);
        $result = $this->suppliers->get([]);
        if ($result['item_count'] > $count) {
            $ignoreList = [];
            foreach ($result['data'] as $row) {
                if (! in_array($row['id'], $this->supplierIdsList)) {
                    $ignoreList[] = $row['id'];
                }
            }
            $this->suppliers->ignore(['supplier_ids' => $ignoreList]);
        }
    }

    /**
     * This function will ignore the suppliers
     *
     * @throws Exception
     */
    #[NoReturn]
    public function unIgnoreSuppliers(): void
    {
        //Fetch ignored suppliers
        $result = $this->suppliers->ignored();
        $ignoredList = [];
        if ($result && ! empty($result['data'])) {
            foreach ($result['data'] as $row) {
                $ignoredList[] = $row['id'];
            }
            $this->suppliers->unignore(['supplier_ids' => $ignoredList]);
        }
    }

    private function processSuppliersData(array $data): void
    {
        foreach ($data as $row) {
            $details = (object) ($row['details'] ?? []);

            if ($this->shouldSkipSupplier($row['id'])) {
                continue;
            }

            $supplierData = $this->extractSupplierData($row, $details);
            $supplier = Supplier::create($supplierData);

            $this->processContactsData($details->contacts ?? [], $supplier->id);
            $this->processAddressesData($details->addresses ?? [], $supplier->id);
        }
    }

    private function shouldSkipSupplier(int $supplierId): bool
    {
        return Supplier::where('promodata_id', $supplierId)->exists();
    }

    private function extractSupplierData(array $row, object $details): array
    {
        return [
            'name' => $row['name'] ?? null,
            'country' => $row['country'] ?? null,
            'active' => $row['active'] ?? null,
            'abn' => $details->abn ?? null,
            'fax' => $details->fax ?? null,
            'email' => $details->email ?? null,
            'brands' => $details->brands ?? null,
            'phone_1' => $details->phone_1 ?? null,
            'phone_2' => $details->phone_2 ?? null,
            'website' => $details->website ?? null,
            'appa_name' => $details->appa_name ?? null,
            'appa_notes' => $details->appa_notes ?? null,
            'appa_profile' => $details->appa_profile ?? null,
            'appa_identifier' => $details->appa_identifier ?? null,
            'promodata_id' => $row['id'],
            'promodata_created_at' => $row['created_at'],
            'promodata_updated_at' => $row['updated_at'],
        ];
    }

    private function processContactsData(array $contacts, int $supplierId, bool $isUpdated = false): void
    {
        // Delete existing contacts for the supplier
        if ($isUpdated) {
            SupplierContacts::where('supplier_id', $supplierId)->delete();
        }

        foreach ($contacts as $contact) {
            $contact = (object) $contact;
            $this->contactsDataToInsert[] = [
                'supplier_id' => $supplierId,
                'name' => $contact->name ?? null,
                'email' => $contact->email ?? null,
                'phone_1' => $contact->phone_1 ?? null,
                'phone_2' => $contact->phone_2 ?? null,
                'position' => $contact->position ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }

    private function processAddressesData(array $addresses, int $supplierId, $isUpdated = false): void
    {
        // Delete existing addresses for the supplier
        if ($isUpdated) {
            SupplierAddresses::where('supplier_id', $supplierId)->delete();
        }

        foreach ($addresses as $address) {
            $address = (object) $address;
            $this->addressesDataToInsert[] = [
                'supplier_id' => $supplierId,
                'type' => $address->type ?? null,
                'state' => $address->state ?? null,
                'suburb' => $address->suburb ?? null,
                'address' => $address->address ?? null,
                'country' => $address->country ?? null,
                'postcode' => $address->postcode ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }

    private function bulkInsertData(): void
    {
        try {
            $this->insertContactsData();
            $this->insertAddressesData();
        } catch (Exception $exception) {
            $this->createLog('ERROR', __LINE__, $exception->getMessage());
        }
    }

    private function insertContactsData(): void
    {
        if (! empty($this->contactsDataToInsert)) {
            SupplierContacts::insert($this->contactsDataToInsert);
            $this->contactsDataToInsert = []; // Clear the array after inserting
        }
    }

    private function insertAddressesData(): void
    {
        if (! empty($this->addressesDataToInsert)) {
            SupplierAddresses::insert($this->addressesDataToInsert);
            $this->addressesDataToInsert = []; // Clear the array after inserting
        }
    }

    private function processUpdateSuppliersData(array $data, string $fromDate): void
    {
        foreach ($data as $row) {
            $details = (object) ($row['details'] ?? []);

            if ($this->shouldSkipSupplierUpdate($row, $fromDate)) {
                continue;
            }
            $supplierData = $this->extractSupplierData($row, $details);

            // Check if a supplier with the given 'promodata_id' already exists
            $existingSupplier = Supplier::where('promodata_id', $row['id'])->first();

            if ($existingSupplier) {
                // Update the existing supplier with the new data
                $existingSupplier->update($supplierData);
                $supplier = $existingSupplier;

                $this->processContactsData($details->contacts ?? [], $supplier->id, true);
                $this->processAddressesData($details->addresses ?? [], $supplier->id, true);
            }
        }
    }

    /**
     * @param  array  $row supplier row data
     * @param  string  $fromDate date which passed from update supplier comamnd
     */
    private function shouldSkipSupplierUpdate(array $row, string $fromDate): bool
    {
        if (Supplier::where('promodata_id', $row['id'])->exists()) {
            if ($fromDate != '') {
                $date = Carbon::create($row['updated_at']);
                $last_updated_date = $date->toDateString();

                return ! ($last_updated_date >= $fromDate);
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
    // Add more methods as needed for fetching, updating, and deleting suppliers
}
