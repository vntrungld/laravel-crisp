# Design for Dynamic Settings Page

## 1. Architecture & Components

This feature will be built using the TALL stack principles, leveraging Livewire for dynamic UI components.

*   **Livewire Component (`CrispSettings.php`)**: This will be the core of the feature.
    *   It will have public properties to hold the settings schema (`$schema`) and the current settings values (`$data`).
    *   The `mount()` method will be responsible for authenticating with the Crisp API, fetching the settings schema for the plugin, and the current settings values for the specific website.
    *   A `save()` method will handle the submission, sending the updated `$data` back to the Crisp API.
    *   It will manage error states, such as API connection failures or validation errors returned from Crisp.

*   **View (`crisp-settings.blade.php`)**: This will be the Livewire component's view.
    *   It will dynamically render the form fields by iterating through the `$schema` variable.
    *   It will use `wire:model` to bind the form inputs to the `$data` property in the component.
    *   A "Save" button will be wired to the `save()` method using `wire:submit.prevent="save"`.
    *   It will display success or error messages based on the component's state.

*   **Service Class (`CrispApiService.php`)**: To keep the Livewire component clean and adhere to the Single Responsibility Principle, a dedicated service class will be created to handle all communication with the Crisp API.

## 2. Data Flow & View Rendering

*   **Initial Load**:
    1.  The `CrispSettings` component's `mount()` method is called on page load.
    2.  The method uses the `CrispApiService` to fetch the JSON schema and the current settings values from the Crisp API.
    3.  The results are stored in the component's public properties, `$schema` and `$data`.

*   **Dynamic Form Rendering**:
    1.  The `crisp-settings.blade.php` view will iterate through the `$schema`. For each field, it will dynamically include a Blade partial corresponding to the field type (e.g., `_textfield.blade.php`, `_checkbox.blade.php`).
    2.  Each input within the partials will be bound to the data using `wire:model="data.field_name"`, creating a two-way binding.

*   **Saving Data**:
    1.  Clicking "Save" triggers the `save()` method in the `CrispSettings` component.
    2.  The component passes the current `$data` property to the `CrispApiService`.
    3.  The service sends this data to the Crisp API.
    4.  The component displays a success or error message based on the API response.

## 3. Error Handling & Testing

*   **Error Handling**:
    *   **API Failures**: If the Crisp API is unreachable on load, an error message will be displayed instead of the form.
    *   **Validation Errors**: If the Crisp API rejects the submitted data, the validation errors will be parsed and displayed next to the relevant form fields.
    *   **Save Failures**: A generic error message will be shown if the save operation fails for other reasons.

*   **Testing**:
    *   Feature tests will be written for the `CrispSettings` Livewire component.
    *   Laravel's `Http::fake()` will be used to mock Crisp API responses.
    *   **Test Scenarios**:
        1.  The settings page renders correctly with data from the API.
        2.  The form can be submitted successfully.
        3.  API connection errors on load are handled gracefully.
        4.  Validation errors from the API are displayed correctly.
