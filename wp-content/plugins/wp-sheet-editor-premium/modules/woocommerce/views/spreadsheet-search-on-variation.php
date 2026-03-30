<?php defined( 'ABSPATH' ) || exit; ?>
<li>
	<label>
		<input type="checkbox" name="search_variations_boolean" :checked="activeFilters.search_variations === 'yes'" @change="activeFilters.search_variations = $el.checked ? 'yes' : ''" /> 
		<input type="hidden" name="search_variations" x-model="activeFilters.search_variations" />
		<?php esc_html_e('Search on variations?', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_attr_e('All the parameters will be used to search on variations instead of the main products.', 'vg_sheet_editor' ); ?>">( ? )</a></label>
</li>