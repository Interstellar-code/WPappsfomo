
const Licensing = ({
    name: 'Licensing',
    components: {
        LinksyForm
    },
    props: {
        tab: Object,
    },
    data() {
        return {
            active: false,
            expires_at: null,
            settings: {
                token: null,
            },
        }
    },
    computed: {
        status: {
            get() {
                if (this.active) {
                    return isNaN(this.expires_at)? 'Lifetime goals' : this.expires_at + ' Days to expire';
                }

                return 'Your License has expired. Kindly add your license key and verify';
            }
        }
    },
    methods: {
        validateSettings: function(values) {
            const errors = {};
            if (!values.token) {
                errors.token = "Token is required";
            }
            return errors;
        },

        saveSettings: async function(values, { setSubmitting }) {
            try {
                var params = urlSearchParams({
                    'action': 'linksy_settings_verify_plugin',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'settings': JSON.stringify({...values})
                });

                const { data: {data} }= await axios.post(ajaxurl, params);

                this.active = true;
                
                if (data.expires_at != 'lifetime' && moment(data.expires_at).isValid()) {
                    this.expires_at = moment(data.expires_at).diff(moment(), 'days');
                } else {
                    this.expires_at = data.expires_at
                }

                this.$toast.success('Licence updated');
            } catch (error) {
                this.$toast.error(error.response?.data?.data || error.message || "failed to save setting");
            } finally {
                setSubmitting(false);
            }
        }
    },
    template: `
        <linksy-form :id="tab.id" :validate="validateSettings" :initial-values="settings" :on-submit="saveSettings"
            v-slot="{
                values,
                errors,
                isSubmitting,
                handleChange,
                handleSubmit,
                setFieldValue,
            }"
        >
            <section>
                <div class="section-content">
                    <div class="row align-items-start w-100">
                        <h3 class="col-3">Status</h3>
                        <div class="col-8">
                            <div class="cube" :class="{active}">
                                <i class="fa fa-circle-dot"></i>&nbsp;Active
                            </div>
                            <p>{{status}}</p>
                        </div>
                    </div>
                </div>
                
                <br />
                <br />

                <div class="section-content" v-if="!active">
                    <div class="row align-items-start w-100">
                        <h3 class="col-3">License Key</h3>
                        <div class="col-8">
                            <input
                                type="text"
                                name="token"
                                :class="{invalid: errors.token}"
                                @input="handleChange"
                            />
                        </div>
                    </div>
                </div>
            </section>
            
            
            <br />
            <div class="d-flex" v-if="!active">
                <br />
                <button :disabled="isSubmitting || active" class="btn-submit btn-app ms-auto" @click="handleSubmit">Verify Licence</button>
            </div>
        </linksy-form>
    `,
    beforeMount() {
        this.expires_at = LINKSY_SETTINGS.expires_at;

        if (this.expires_at == 'lifetime') {
            this.active = true;
        } else if (moment(this.expires_at).isValid()) {
            this.expires_at = moment(LINKSY_SETTINGS.expires_at).diff(moment(), 'days');

            if (this.expires_at > 0) {
                this.active = true;
            }
        }
    }
});