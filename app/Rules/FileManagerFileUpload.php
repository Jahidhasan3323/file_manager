<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class FileManagerFileUpload implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $allowed_file_extension = config('filesystems.allowed_file_extension');
        $max_file_size = config('filesystems.max_file_size');
        $fileWithExt = basename($value->getClientOriginalName(), '.part');
        $array = explode('.', $fileWithExt);
        $fileExt = end($array);
        if($value->getSize() <= $max_file_size && in_array($fileExt,$allowed_file_extension)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This file type is not allowed.';
    }
}
