<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ListHelper;
use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Rap2hpoutre\FastExcel\FastExcel;

class SubCategoryTranslationController extends Controller
{
    private $failed_list = [];

    // Fields or columns that must be present in each row.
    private $required_fields = ['slug', 'lang', 'name', 'description'];

    /**
     * Display the translation form for a specific sub-category.
     *
     * @param  SubCategory  $subCategory  The sub-category instance.
     * @param  string  $selected_language  The selected language for the translation.
     * @return \Illuminate\View\View The translation form view.
     */
    public function showTranslationForm(SubCategory $subCategory, string $selected_language)
    {
        $available_languages = ListHelper::availableTranslationLocales();

        if (! $available_languages->count()) {
            return back()->with('warning', trans('messages.no_translation_available'));
        }

        if ($selected_language == config('system_settings.default_language')) {
            return redirect()->route('admin.catalog.subcategory.translate.form', ['subCategory' => $subCategory, 'language' => $available_languages->first()->code]);
        }

        $subCategory_translation = $subCategory->translations()->where('lang', $selected_language)->firstOrNew([
            'sub_category_id' => $subCategory->id,
            'lang' => $selected_language,
            'translation' => [],
        ]);

        return view('admin.category.subcategory._translation', compact('subCategory', 'subCategory_translation', 'available_languages', 'selected_language'));
    }

    /**
     * Store the translation for a sub-category item.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeTranslation(SubCategory $subCategory, Request $request)
    {
        $existing_translation = $subCategory->hasTranslation($request->lang);

        $subCategory_translation = $subCategory->translations()->where('lang', $request->lang)->firstOrNew([
            'sub_category_id' => $subCategory->id,
            'lang' => $request->lang,
        ]);

        $subCategory_translation->translation = [
            'name' => $request->input('name'),
        ];

        $subCategory_translation->save();

        return back()->with('success', trans($existing_translation ? 'messages.updated' : 'messages.created', ['model' => 'SubCategory Translation']));
    }

    /**
     * Display the form for bulk translation of sub-category data.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function showBulkUploadForm()
    {
        return view('admin.category.subcategory._bulk_translation_form');
    }

    public function uploadBulkTranslation(Request $request)
    {
        $this->validate($request, [
            'subCategoryTranslations' => 'required|file|mimes:csv',
        ]);

        $path = $request->file('subCategoryTranslations')->getRealPath();
        $records = array_map('str_getcsv', file($path));

        // Validations check for csv_import_limit
        if (count($records) > get_csv_import_limit()) {
            $err = (new MessageBag)->add('error', trans('validation.upload_rows', ['rows' => get_csv_import_limit()]));

            return back()->withErrors($err);
        }

        // Get field names from header column
        $fields = array_map('strtolower', $records[0]);
        // Check if any column headers has been changed or missing.
        $missing_fields = array_diff($this->required_fields, $fields);
        if (! empty($missing_fields)) {
            $err = (new MessageBag)->add('error', trans('validation.csv_upload_invalid_data'));

            return back()->withErrors($err);
        }

        // Remove the header column
        array_shift($records);

        $rows = [];
        foreach ($records as $record) {
            if (count($fields) != count($record)) {
                $err = (new MessageBag)->add('error', trans('validation.csv_upload_invalid_data'));

                return back()->withErrors($err);
            }

            // Decode unwanted html entities
            $record = array_map('html_entity_decode', $record);

            $search = ['&#39;'];
            $replace = [' \' '];
            $record = str_replace($search, $replace, $record);

            // Set the field name as key
            $record = array_combine($fields, $record);

            // Get the clean data
            $rows[] = clear_encoding_str($record);
        }

        return view('admin.category.subcategory._translation_bulk_upload_review', compact('rows'));
    }

    /**
     * Imports bulk translations for sub-categories.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request object.
     * @return \Illuminate\View\View The response object or the view.
     */
    public function importBulkTranslation(Request $request)
    {
        // Reset the Failed list
        $this->failed_list = [];

        $records = $request->input('data');
        foreach ($records as $row) {
            $data = unserialize($row);

            if ($this->dataHasMissingField($data)) {
                $this->pushIntoFailed($data, trans('help.missing_required_data'));

                continue;
            }

            // perform uploading to database below
            try {
                $subCategory = SubCategory::where('slug', $data['slug'])->first();

                if (! $subCategory) {
                    $this->pushIntoFailed($data, trans('help.category_not_found'));
                }

                $subCategory_translation = $subCategory->translations()->where('lang', $data['lang'])->firstOrNew([
                    'sub_category_id' => $subCategory->id,
                    'lang' => $data['lang'],
                ]);

                $subCategory_translation->translation = [
                    'name' => $data['name'],
                ];

                $subCategory_translation->save();
            } catch (\Exception $error) {
                $this->pushIntoFailed($data, $error->getMessage());

                \Log::error($error);

                continue;
            }
        }
        $request->session()->flash('success', trans('messages.imported', ['model' => trans('SubCategory Translation')]));

        $failed_rows = $this->getFailedList();

        if (empty($failed_rows)) {
            return redirect()->route('admin.catalog.category.index');
        }

        return view('admin.category.subcategory._translation_import_failed', compact('failed_rows'));
    }

    /**
     * Check if the given data has any missing fields.
     *
     * @param  array|null  $data  The data to be checked.
     * @return bool Returns true if the data has missing fields, false otherwise.
     */
    private function dataHasMissingField($data)
    {
        if (! isset($data) || ! is_array($data)) {
            return true; // Data is not set or not an array
        }

        foreach ($this->required_fields as $field) {
            if (! isset($data[$field])) {
                return true; // When Field is missing
            }
        }

        return false;
    }

    /**
     * Download the template file for sub-category translations.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse the template csv file
     */
    public function downloadTemplate()
    {
        $available_languages = ListHelper::availableTranslationLocales();
        $default_language = config('system_settings.default_language');

        $sub_categories = SubCategory::get(['id', 'name', 'slug', 'description']);

        $rows = [];
        foreach ($sub_categories as $sub_category) {
            $rows[] = [
                'slug' => $sub_category->slug,
                'lang' => $default_language,
                'name' => $sub_category->name,
            ];

            foreach ($available_languages as $language) {
                $sub_category_translation = $sub_category->translations()->where('lang', $language->code)->first();

                $rows[] = [
                    'slug' => $sub_category->slug,
                    'lang' => $language->code,
                    'name' => $sub_category_translation->translation['name'] ?? '',
                ];
            }
        }

        return (new FastExcel(collect($rows)))->configureCsv(',', '"', 'UTF-8')->download('subCategoryTranslations.csv');
    }

    /**
     * Download the failed rows as an Excel file.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadFailedRows(Request $request)
    {
        foreach ($request->input('data') as $row) {
            $data[] = unserialize($row);
        }

        return (new FastExcel(collect($data)))->download('failed_rows.xlsx');
    }

    /**
     * Push New value Into Failed List
     *
     * @param  string  $reason
     * @return void
     */
    private function pushIntoFailed(array $data, $reason = null)
    {
        $row = [
            'data' => $data,
            'reason' => $reason,
        ];

        array_push($this->failed_list, $row);
    }

    /**
     * Return the failed list
     *
     * @return array
     */
    private function getFailedList()
    {
        return $this->failed_list;
    }
}
