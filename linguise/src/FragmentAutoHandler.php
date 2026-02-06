<?php

namespace Linguise\WordPress;

use Linguise\WordPress\FragmentBase;
use Linguise\Vendor\JsonPath\JsonObject;
use Linguise\Vendor\Linguise\Script\Core\Debug;

defined('ABSPATH') || die('');

/**
 * Fragment auto mode handler.
 */
class FragmentAutoHandler extends FragmentBase
{
    /**
     * Apply the translated fragments for the auto mode.
     *
     * @param array $json_data The JSON data to be injected
     * @param array $fragments The array of fragments to be injected, from intoJSONFragments
     *
     * @return array
     */
    public static function applyTranslatedFragmentsForAuto($json_data, $fragments)
    {
        $json_path = new JsonObject($json_data);
        // Warn if $json_data is empty
        if (empty($json_path->getValue())) {
            return false;
        }

        foreach ($fragments as $fragment) {
            if (isset($fragment['skip']) && $fragment['skip']) {
                // If skip is true, then don't replace this fragment (but remove it)
                continue;
            }
            // remove the html fragment from the translated page
            $decoded_key = self::unwrapKey($fragment['key']);
            try {
                $json_path->set('$.' . $decoded_key, $fragment['value']);
            } catch (\Linguise\Vendor\JsonPath\InvalidJsonPathException $e) { // @codeCoverageIgnore
                Debug::log('Failed to set key in auto: ' . $decoded_key . ' -> ' . $e->getMessage()); // @codeCoverageIgnore
            }
        }

        $replaced_json = $json_path->getValue();
        return $replaced_json;
    }
}
