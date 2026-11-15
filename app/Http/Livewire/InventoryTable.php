<?php

namespace App\Http\Livewire;

use App\Models\Inventory;
use App\Models\User;
use App\Notifications\GeneralNotificationJust;
use App\Traits\System;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class InventoryTable extends PowerGridComponent
{
    use System, WithExport;

    public string $tableName = 'inventory_table';

    public bool $showUpdateMessages = true;

    /** @var string|null */
    public $itemCode = null;

    /*
    |--------------------------------------------------------------------------
    |  Features Setup
    |--------------------------------------------------------------------------
    | Setup Table's general features
    |
    */
    public function setUp(): array
    {
        $this->showCheckBox()->persist(['columns', 'filters']);

        return [
            PowerGrid::exportable('Inventory List')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV)
                ->stripTags(true),
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount('full'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    |  Datasource
    |--------------------------------------------------------------------------
    | Provides data to your Table using a Model or Collection
    |
    */

    /**
     * PowerGrid datasource.
     *
     * @return Builder<Inventory>
     */
    public function datasource(): Builder
    {
        return Inventory::query();
    }

    /*
    |--------------------------------------------------------------------------
    |  Relationship Search
    |--------------------------------------------------------------------------
    | Configure here relationships to be used by the Search and Table Filters.
    |
    */

    /**
     * Relationship search.
     *
     * @return array<string, array<int, string>>
     */
    public function relationSearch(): array
    {
        return [];
    }

    public function ShowQR(string $value): void
    {
        $this->itemCode = $value;
        $this->dispatch('itemCodeChanged', $this->itemCode);
    }

    /*
    |--------------------------------------------------------------------------
    |  Add Column
    |--------------------------------------------------------------------------
    | Make Datasource fields available to be used as columns.
    | You can pass a closure to transform/modify the data.
    |
    | ❗ IMPORTANT: When using closures, you must escape any value coming from
    |    the database using the `e()` Laravel Helper function.
    |
    */
    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('item_code')
            ->add('action_buttons', function (Inventory $model) {
                return '<div class="flex gap-2"><button wire:click.prefetch="ShowQR(\''.$model->item_code.'\')" type="button" class="btn-sm btn-blue float-right" data-bs-toggle="modal" data-modal-toggle="showQRcode"><i class="bi bi-qr-code"></i></button><a target="_blank" href="'.route('inventory.show', $model->item_code).'" class="btn-sm btn-green"><i class="bi bi-eye"></i></a></div>';
            })
            ->add('item_name')
            ->add('received_date_formatted', function (Inventory $model) {
                return Carbon::parse($model->received_date)->format('d/m/Y');
            })
            ->add('indent_no')
            ->add('supplier_name')
            ->add('model')
            ->add('serial_number')
            ->add('properties', function (Inventory $model) {
                return $model->properties;
            })
            ->add('book_no')
            ->add('folio_no')
            ->add('value', function (Inventory $model) {
                return 'Rs. '.$model->value;
            })
            ->add('budget_allocation')
            ->add('location')
            ->add('remark')
            ->add('created_at_formatted', function (Inventory $model) {
                return Carbon::parse($model->created_at)->format('d/m/Y H:i:s');
            })
            ->add('updated_at_formatted', function (Inventory $model) {
                return Carbon::parse($model->updated_at)->format('d/m/Y H:i:s');
            });
    }

    /*
    |--------------------------------------------------------------------------
    |  Include Columns
    |--------------------------------------------------------------------------
    | Include the columns added columns, making them visible on the Table.
    | Each column can be configured with properties, filters, actions...
    |
    */

    /**
     * PowerGrid Columns.
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [

            Column::add()
                ->title('ITEM CODE')
                ->field('item_code')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('INFO')
                ->field('action_buttons')
                ->visibleInExport(false),

            Column::add()
                ->title('ITEM NAME')
                ->field('item_name')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('RECEIVED DATE')
                ->field('received_date_formatted', 'received_date')
                ->searchable()
                ->sortable(),

            Column::add()
                ->title('INDENT No.')
                ->field('indent_no')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('SUPPLIER NAME')
                ->field('supplier_name')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('MODEL')
                ->field('model')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('SERIAL NUMBER')
                ->field('serial_number')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('BOOK NO')
                ->field('book_no')
                ->editOnClick($this->can('edit inventory')),

            Column::add()
                ->title('FOLIO NO')
                ->field('folio_no')
                ->editOnClick($this->can('edit inventory')),

            Column::add()
                ->title('PROPERTIES')
                ->field('properties')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('VALUE')
                ->field('value'),

            Column::add()
                ->title('BUDGET ALLOCATION')
                ->field('budget_allocation')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('LOCATION')
                ->field('location')
                ->sortable()
                ->searchable()
                ->editOnClick($this->can('edit inventory')),

            Column::add()
                ->title('REMARK')
                ->field('remark')
                ->sortable()
                ->editOnClick($this->can('edit inventory'))
                ->searchable(),

            Column::add()
                ->title('CREATED AT')
                ->field('created_at_formatted', 'created_at')
                ->searchable()
                ->sortable(),

            Column::add()
                ->title('UPDATED AT')
                ->field('updated_at_formatted', 'updated_at')
                ->searchable()
                ->sortable(),

            Column::action('Action'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('item_code'),
            Filter::inputText('item_name'),
            Filter::datepicker('received_date'),
            Filter::inputText('indent_no'),
            Filter::inputText('supplier_name'),
            Filter::inputText('model'),
            Filter::inputText('serial_number'),
            Filter::number('book_no'),
            Filter::number('folio_no'),
            Filter::number('value'),
            Filter::inputText('budget_allocation'),
            Filter::inputText('location'),
            Filter::datepicker('created_at'),
            Filter::datepicker('updated_at'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Actions Method
    |--------------------------------------------------------------------------
    | Enable the method below only if the Routes below are defined in your app.
    |
    */

    /**
     * PowerGrid Inventory Action Buttons.
     *
     * @return array<int, Button>
     */
    public function actions(Inventory $row): array
    {
        return [
            Button::add('delete-item')
                ->slot('Delete')
                ->class('btn-sm btn-red')
                ->dispatch('delete-item', ['id' => $row->id]),
        ];
    }

    /**
     * PowerGrid Inventory Action Rules.
     *
     * @return array<int, RuleActions>
     */
    public function actionRules(): array
    {
        return [
            Rule::button('delete-item')
                ->when(fn ($item) => ! $this->can('delete inventory'))
                ->hide(),
        ];
    }

    public function onUpdatedEditable(string|int $id, string $field, string $value): void
    {
        try {
            Inventory::query()->findOrFail($id)
                ->update([
                    $field => $value,
                ]);
        } catch (QueryException $exception) {
            //
        }
    }

    #[On('delete-item')]
    public function deleteItem(int $id)
    {
        if ($this->can('delete inventory')) {
            try {
                $inventory = Inventory::findOrFail($id);
                $inventory->delete();
                $this->passed($inventory->item_code.' Inventory Item was deleted');

                $users = User::all();
                foreach ($users as $user) {
                    if ($user->hasRole('Head of the Department')) {
                        Notification::send($user, new GeneralNotificationJust('Inventory Item was deleted by '.auth()->user()->name));
                    }
                }

                session()->flash('toast_success', 'Item deleted successfully!');

                return redirect(request()->header('Referer'));
            } catch (\Throwable $th) {
                $this->failed($th);
            }
        }
    }
}
