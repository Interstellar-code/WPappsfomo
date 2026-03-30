const LinksyPhraseSettings = ({
    name: 'LinksyPhraseSettings',
    components: {
        LinksyForm,
    },
    props: {
        settings: {
            type: Object,
            default: {}
        }
    },
    data() {
        return {
            initialValues: {}
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
        onSubmit: function(values, { setSubmitting }) {
            this.$emit('onSetting', values);
        }
    },
    template: `
        <linksy-form class="linksy-phrase linksy-phrase-settings" :validate="validate" :initial-values="initialValues" :on-submit="onSubmit"
            v-slot="{
                values,
                errors,
                isSubmitting,
                handleSubmit,
            }"
        >
            <h4>Settings</h4>
            <div class="linksy-phrase-settings-body">
                <div>
                    <input type="checkbox" v-model="values.suggestions_ignored_post" />
                    <span>Stop showing this post on Post Edit Page</span>
                </div>
                <hr />
                <div>
                    <input type="checkbox" v-model="values.inbound_ignored_post" />
                    <span>Stop showing this post on Add Inbound Links Page</span>
                </div>
                <hr />
                <div>
                    <input type="checkbox" v-model="values.show_suggestions_used_phrases" />
                    <span>Suggest used keywords</span>
                </div>
            </div>

            <div class="linksy-phrase-actions">
                <button type="button" class="cancel btn-default"><i class="fa fa-times"></i>Cancel</button>
                <button type="button" class="apply btn-app" @click="handleSubmit"><i class="fa fa-check"></i>Apply</button>
            </div>
        </linksy-form>
    `,
    beforeMount() {
        const {
            show_single_words,
            inbound_ignored_post,
            suggestions_ignored_post,
            show_suggestions_used_phrases,
        } = this.settings;

        this.initialValues = {
            // show_single_words,
            inbound_ignored_post,
            suggestions_ignored_post,
            show_suggestions_used_phrases,
        }
    }
});