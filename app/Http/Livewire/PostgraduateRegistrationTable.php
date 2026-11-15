<?php

namespace App\Http\Livewire;

use App\Models\PostgraduateRegistration;
use App\Traits\System;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class PostgraduateRegistrationTable extends PowerGridComponent
{
    use System, WithExport;

    public string $tableName = 'postgraduate_registration_table';

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
            PowerGrid::exportable('PG Registration '.now()->toDateString())
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

    public string $primaryKey = 'postgraduate_registrations.nic';

    public string $sortField = 'postgraduate_registrations.nic';

    public function datasource(): ?Builder
    {
        return PostgraduateRegistration::query()->with('getCompany', 'getUniversity', 'getMembership', 'getReferee');
    }

    /*
    |--------------------------------------------------------------------------
    |  Relationship Search
    |--------------------------------------------------------------------------
    | Configure here relationships to be used by the Search and Table Filters.
    |
    */
    public function relationSearch(): array
    {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    |  Add Column
    |--------------------------------------------------------------------------
    | Make Datasource fields available to be used as columns.
    | You can pass a closure to transform/modify the data.
    |
    */
    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('nic')
            ->add('applied_degree', function (PostgraduateRegistration $model) {
                if ($model->applied_degree == 'Postgraduate Programme in Engineering Management') {
                    return 'Engineering Management';
                }
                if ($model->applied_degree == 'Postgraduate Programme in Manufacturing Engineering') {
                    return 'Manufacturing Engineering';
                }
            })
            ->add('degree_cat', function (PostgraduateRegistration $model) {
                if ($model->degree_cat == 'Postgraduate Diploma (PG Dip)') {
                    return 'PG Dip';
                }
                if ($model->degree_cat == 'Master of Science (MSc)') {
                    return 'MSc';
                }
                if ($model->degree_cat == 'Master of Science of Engineering (MSc Eng)') {
                    return 'MSc Eng';
                }
            })
            ->add('fname')
            ->add('lname')
            ->add('fullname')
            ->add('email')
            ->add('phone')
            ->add('birthday_formatted', function (PostgraduateRegistration $model) {
                return Carbon::parse($model->birthday)->format('d/m/Y');
            })
            ->add('gender')
            ->add('address')
            ->add('employment')
            ->add('noofuniversities')
            ->add('noofcompanies')
            ->add('noofmemberships')
            ->add('year_formatted', function (PostgraduateRegistration $model) {
                return Carbon::parse($model->year)->format('Y');
            })
            ->add('file_path')
            ->add('r1_is_submit', function (PostgraduateRegistration $model) {
                if ($model->r1_is_submit) {
                    return 'Submitted';
                } else {
                    return 'Not Submit';
                }
            })
            ->add('r2_is_submit', function (PostgraduateRegistration $model) {
                if ($model->r2_is_submit) {
                    return 'Submitted';
                } else {
                    return 'Not Submit';
                }
            })
            ->add('ip')
            ->add('random_phase')
            ->add('created_at_formatted', function (PostgraduateRegistration $model) {
                return Carbon::parse($model->created_at)->format('d/m/Y');
            })
            ->add('updated_at_formatted', function (PostgraduateRegistration $model) {
                return Carbon::parse($model->updated_at)->format('d/m/Y H:i:s');
            })
            ->add('degree1', function (PostgraduateRegistration $model) {
                if ($model->noofuniversities >= 1) {
                    return $model->getUniversity[0]->degreetype.
                        ', '.$model->getUniversity[0]->university.
                        ', '.$model->getUniversity[0]->specification.
                        ', '.$model->getUniversity[0]->year.
                        ', '.$model->getUniversity[0]->class.
                        ', '.$model->getUniversity[0]->credits;
                } else {
                    return '-';
                }
            })
            ->add('degree2', function (PostgraduateRegistration $model) {
                if ($model->noofuniversities >= 2) {
                    return $model->getUniversity[1]->degreetype.
                        ', '.$model->getUniversity[1]->university.
                        ', '.$model->getUniversity[1]->specification.
                        ', '.$model->getUniversity[1]->year.
                        ', '.$model->getUniversity[1]->class.
                        ', '.$model->getUniversity[1]->credits;
                } else {
                    return '-';
                }
            })
            ->add('degree3', function (PostgraduateRegistration $model) {
                if ($model->noofuniversities >= 3) {
                    return $model->getUniversity[2]->degreetype.
                        ', '.$model->getUniversity[2]->university.
                        ', '.$model->getUniversity[2]->specification.
                        ', '.$model->getUniversity[2]->year.
                        ', '.$model->getUniversity[2]->class.
                        ', '.$model->getUniversity[2]->credits;
                } else {
                    return '-';
                }
            })
            ->add('degree4', function (PostgraduateRegistration $model) {
                if ($model->noofuniversities >= 4) {
                    return $model->getUniversity[3]->degreetype.
                        ', '.$model->getUniversity[3]->university.
                        ', '.$model->getUniversity[3]->specification.
                        ', '.$model->getUniversity[3]->year.
                        ', '.$model->getUniversity[3]->class.
                        ', '.$model->getUniversity[3]->credits;
                } else {
                    return '-';
                }
            })
            ->add('company1', function (PostgraduateRegistration $model) {
                if ($model->noofcompanies >= 1) {
                    return $model->getCompany[0]->position.
                        ', '.$model->getCompany[0]->employer.
                        ', '.$model->getCompany[0]->period;
                } else {
                    return '-';
                }
            })
            ->add('company2', function (PostgraduateRegistration $model) {
                if ($model->noofcompanies >= 2) {
                    return $model->getCompany[1]->position.
                        ', '.$model->getCompany[1]->employer.
                        ', '.$model->getCompany[1]->period;
                } else {
                    return '-';
                }
            })
            ->add('company3', function (PostgraduateRegistration $model) {
                if ($model->noofcompanies >= 3) {
                    return $model->getCompany[2]->position.
                        ', '.$model->getCompany[2]->employer.
                        ', '.$model->getCompany[2]->period;
                } else {
                    return '-';
                }
            })
            ->add('company4', function (PostgraduateRegistration $model) {
                if ($model->noofcompanies >= 4) {
                    return $model->getCompany[3]->position.
                        ', '.$model->getCompany[3]->employer.
                        ', '.$model->getCompany[3]->period;
                } else {
                    return '-';
                }
            })
            ->add('company5', function (PostgraduateRegistration $model) {
                if ($model->noofcompanies >= 5) {
                    return $model->getCompany[4]->position.
                        ', '.$model->getCompany[4]->employer.
                        ', '.$model->getCompany[4]->period;
                } else {
                    return '-';
                }
            })
            ->add('membership1', function (PostgraduateRegistration $model) {
                if ($model->noofmemberships >= 1) {
                    return $model->getMembership[0]->membershipcat.
                        ', '.$model->getMembership[0]->organization.
                        ', '.$model->getMembership[0]->membershipno;
                } else {
                    return '-';
                }
            })
            ->add('membership2', function (PostgraduateRegistration $model) {
                if ($model->noofmemberships >= 2) {
                    return $model->getMembership[1]->membershipcat.
                        ', '.$model->getMembership[1]->organization.
                        ', '.$model->getMembership[1]->membershipno;
                } else {
                    return '-';
                }
            })
            ->add('membership3', function (PostgraduateRegistration $model) {
                if ($model->noofmemberships >= 3) {
                    return $model->getMembership[2]->membershipcat.
                        ', '.$model->getMembership[2]->organization.
                        ', '.$model->getMembership[2]->membershipno;
                } else {
                    return '-';
                }
            })
            ->add('membership4', function (PostgraduateRegistration $model) {
                if ($model->noofmemberships >= 4) {
                    return $model->getMembership[3]->membershipcat.
                        ', '.$model->getMembership[3]->organization.
                        ', '.$model->getMembership[3]->membershipno;
                } else {
                    return '-';
                }
            })
            ->add('membership5', function (PostgraduateRegistration $model) {
                if ($model->noofmemberships >= 5) {
                    return $model->getMembership[4]->membershipcat.
                        ', '.$model->getMembership[4]->organization.
                        ', '.$model->getMembership[4]->membershipno;
                } else {
                    return '-';
                }
            })
            ->add('referee1', function (PostgraduateRegistration $model) {
                return $model->getReferee[0]->name.
                    ', '.$model->getReferee[0]->designation.
                    ', '.$model->getReferee[0]->email;
            })
            ->add('referee2', function (PostgraduateRegistration $model) {
                return $model->getReferee[1]->name.
                    ', '.$model->getReferee[1]->designation.
                    ', '.$model->getReferee[1]->email;
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
    public function columns(): array
    {
        return [
            Column::add()
                ->title(__('NIC'))
                ->field('nic')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('APPLIED DEGREE'))
                ->field('applied_degree')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('DEGREE CAT'))
                ->field('degree_cat')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('FIRST NAME'))
                ->field('fname')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('LAST NAME'))
                ->field('lname')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('FULL NAME'))
                ->field('fullname')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('EMAIL'))
                ->field('email')
                ->sortable(),

            Column::add()
                ->title(__('PHONE'))
                ->field('phone')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('BIRTHDAY'))
                ->field('birthday_formatted')
                ->sortable(),

            Column::add()
                ->title(__('ADDRESS'))
                ->field('address')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('GENDER'))
                ->field('gender')
                ->sortable(),

            Column::add()
                ->title(__('1st DEGREE'))
                ->field('degree1'),

            Column::add()
                ->title(__('2nd DEGREE'))
                ->field('degree2'),

            Column::add()
                ->title(__('3rd DEGREE'))
                ->field('degree3'),

            Column::add()
                ->title(__('4th DEGREE'))
                ->field('degree4'),

            Column::add()
                ->title(__('EMPLOYMENT'))
                ->field('employment')

                ->sortable(),

            Column::add()
                ->title(__('1st COMPANY'))
                ->field('company1'),

            Column::add()
                ->title(__('2nd COMPANY'))
                ->field('company2'),

            Column::add()
                ->title(__('3rd COMPANY'))
                ->field('company3'),

            Column::add()
                ->title(__('4th COMPANY'))
                ->field('company4'),

            Column::add()
                ->title(__('5th COMPANY'))
                ->field('company5'),

            Column::add()
                ->title(__('1st MEMBERSHIP'))
                ->field('membership1'),

            Column::add()
                ->title(__('2nd MEMBERSHIP'))
                ->field('membership2'),

            Column::add()
                ->title(__('3rd MEMBERSHIP'))
                ->field('membership3'),

            Column::add()
                ->title(__('4th MEMBERSHIP'))
                ->field('membership4'),

            Column::add()
                ->title(__('5th MEMBERSHIP'))
                ->field('membership5'),

            // Column::add()
            //     ->title(__('NOOFUNIVERSITIES'))
            //     ->field('noofuniversities')
            //     ->makeInputRange(),

            // Column::add()
            //     ->title(__('NOOFCOMPANIES'))
            //     ->field('noofcompanies')
            //     ->makeInputRange(),

            // Column::add()
            //     ->title(__('NOOFMEMBERSHIPS'))
            //     ->field('noofmemberships')
            //     ->makeInputRange(),

            // Column::add()
            //     ->title(__('YEAR'))
            //     ->field('year_formatted')
            //     ->searchable()
            //     ->sortable()
            //     ->makeInputDatePicker('year'),

            // Column::add()
            //     ->title(__('FILE PATH'))
            //     ->field('file_path')
            //     ->sortable()
            //     ->searchable()
            //     ,

            Column::add()
                ->title(__('REFEREE 1'))

                ->field('r1_is_submit'),

            Column::add()
                ->title(__('REFEREE 1 DETAILS'))
                ->field('referee1'),

            Column::add()
                ->title(__('REFEREE 2'))

                ->field('r2_is_submit'),

            Column::add()
                ->title(__('REFEREE 2 DETAILS'))
                ->field('referee2'),

            // Column::add()
            //     ->title(__('IP'))
            //     ->field('ip')
            //     ->sortable()
            //     ->searchable()
            //     ,

            // Column::add()
            //     ->title(__('RANDOM PHASE'))
            //     ->field('random_phase')
            //     ->sortable()
            //     ->searchable()
            //     ,

            Column::add()
                ->title(__('REGISTERED ON'))
                ->field('created_at_formatted')
                ->sortable(),

            // Column::add()
            //     ->title(__('UPDATED AT'))
            //     ->field('updated_at_formatted')
            //     ->searchable()
            //     ->sortable()
            //     ->makeInputDatePicker('updated_at'),

        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('nic'),
            Filter::inputText('applied_degree'),
            Filter::inputText('degree_cat'),
            Filter::inputText('fname'),
            Filter::inputText('lname'),
            Filter::inputText('fullname'),
            Filter::select('employment', 'employment')
                ->dataSource(PostgraduateRegistration::select('employment')->distinct()->get())
                ->optionLabel('employment')
                ->optionValue('employment'),
            Filter::boolean('r1_is_submit')
                ->label('Submitted', 'Not Submit'),
            Filter::boolean('r2_is_submit')
                ->label('Submitted', 'Not Submit'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Actions Method
    |--------------------------------------------------------------------------
    | Enable this section only when you have defined routes for these actions.
    |
    */

    /*
    public function actions(): array
    {
       return [
           Button::add('edit')
               ->caption(__('Edit'))
               ->class('bg-indigo-500 text-white')
               ->route('postgraduate-registration.edit', ['postgraduate-registration' => 'id']),

           Button::add('destroy')
               ->caption(__('Delete'))
               ->class('bg-red-500 text-white')
               ->route('postgraduate-registration.destroy', ['postgraduate-registration' => 'id'])
               ->method('delete')
        ];
    }
    */

    /*
    |--------------------------------------------------------------------------
    | Edit Method
    |--------------------------------------------------------------------------
    | Enable this section to use editOnClick() or toggleable() methods
    |
    */

    /*
    public function update(array $data ): bool
    {
       try {
           $updated = PostgraduateRegistration::query()->find($data['id'])->update([
                $data['field'] => $data['value']
           ]);
       } catch (QueryException $exception) {
           $updated = false;
       }
       return $updated;
    }

    public function updateMessages(string $status, string $field = '_default_message'): string
    {
        $updateMessages = [
            'success'   => [
                '_default_message' => __('Data has been updated successfully!'),
                //'custom_field' => __('Custom Field updated successfully!'),
            ],
            'error' => [
                '_default_message' => __('Error updating the data.'),
                //'custom_field' => __('Error updating custom field.'),
            ]
        ];

        return ($updateMessages[$status][$field] ?? $updateMessages[$status]['_default_message']);
    }
    */

    public function template(): ?string
    {
        // return \App\Http\Livewire\PowerGrid\MyCustomTailwindTemplate::class;
        return null;
    }
}
