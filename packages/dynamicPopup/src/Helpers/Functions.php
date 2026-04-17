<?php

use App\Models\Image;
use Illuminate\Support\Facades\Cache;

if (!function_exists('get_popup_data')) {
  /**
   * Get dynamic popup data
   */
  function get_popup_data()
  {
    return Cache::rememberForever('dynamic_popup', function () {
      $data = get_from_option_table('dynamic_popup', ['type' => 'newsletter', 'delay' => 2000, 'css' => '']);
      $data['delay_in_sec'] = $data['delay'] / 1000; // Set the value in seconds

      $img = Image::where([
        ['imageable_type', '=', 'App\Models\System'],
        ['type', '=', 'popup']
      ])->first();

      if ($img) {
        $data['background_img'] = get_storage_file_url($img->path, 'full');
      } else {
        $data['background_img'] = asset('images/placeholders/popup.png');
      }

      return $data;
    });
  }
}
