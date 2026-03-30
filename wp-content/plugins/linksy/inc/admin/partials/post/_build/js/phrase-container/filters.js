const LinksyPhraseFilters = ({
    name: 'LinksyPhraseFilters',
    components: {
        LinksyForm,
        LinksyDropdown,
    },
    props: {
        types: {
            type: Array,
            default: []
        },
        filters: {
            type: Object,
            default: {}
        },
        categories: {
            type: Array,
            default: []
        }
    },
    data() {
        return {
            date: ''
        }
    },
    methods: {
        validate: function(values) {
            const errors = {};

            if (values.pub_date) {
                // todo validate dates
            }
            
            return errors;
        },
        onCancel() {
            this.date = '';
            
            this.$emit('onFilter', {
                types: [],
                categories: [],
                date: '',
                disable_categories: false
            });
        },

        onSubmit: function(values, { setSubmitting }) {
            this.$emit('onFilter', {...values, date: this.date});
            setSubmitting(false)
        }
    },
    template: `
        <linksy-form class="linksy-phrase linksy-phrase-filters" enableReinitialize :validate="validate" :initial-values="filters" :on-submit="onSubmit"
            v-slot="{
                values,
                errors,
                handleChange,
                handleSubmit,
                setFieldValue,
            }"
        >
            <h4>Filter to apply restrictions to the suggested pages.</h4>
            <div class="linksy-phrase-filters-body">
                <div>
                    <span>Post Types</span>
                    <linksy-dropdown
                        multiple
                        :items="types"
                        placeholder="0 Added"
                        :initialValue="values.types"
                        @selected="e => setFieldValue('types', e)"
                    >
                    </linksy-dropdown>
                </div>
                <hr />
                <div>
                    <span>Same Category</span>
                    <input type="checkbox" v-model="values.disable_categories" />
                </div>
                <div>
                    <span>Categories</span>
                    <linksy-dropdown
                        multiple
                        :items="categories"
                        placeholder="0 Added"
                        :disabled="values.disable_categories"
                        :initialValue="values.categories"
                        @selected="e => setFieldValue('categories', e)"
                    >
                    </linksy-dropdown>
                </div>
                <hr />
                <div>
                    <span>Pub. Date</span>
                    <input type="text" class="daterangepickerinput" placeholder="All Dates" readonly name="date" :value="date" />
                </div>
            </div>

            <div class="linksy-phrase-actions">
                <button type="button" class="btn-default" @click="onCancel"><i class="fa fa-times"></i>Reset</button>
                <button type="button" class="btn-app" @click="handleSubmit"><i class="fa fa-check"></i>Apply</button>
            </div>
        </linksy-form>
    `,
    mounted() {
        if (this.filters?.date) {
            this.date = this.filters?.date;
        }

        jQuery('.daterangepickerinput').daterangepicker({
            opens: 'left',
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: DateParser.parse(LINKSY.date_format),
            },
            ranges: {
                'Today': [moment(), moment()],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 3 Months': [moment().subtract(3, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }).on('apply.daterangepicker', (ev, picker) => {
            jQuery('.daterangepickerinput').val(picker.chosenLabel).trigger( "blur" );
            if (picker.chosenLabel == 'Today') {
                picker.endDate = picker.endDate.add(1, 'days');
            }
            this.date = picker.startDate.format(DateParser.parse(LINKSY.date_format))+' - '+picker.endDate.format(DateParser.parse(LINKSY.date_format));
        }).on('cancel.daterangepicker', (ev, picker) => {
            jQuery('.daterangepickerinput').val('');
            this.date = '';
        });
    }
});