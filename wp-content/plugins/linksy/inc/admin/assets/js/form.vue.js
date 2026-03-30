const LinksyForm = ({
    name: 'LinksyForm',
    props: {
        initialValues: {
            type: Object,
            required: true
        },
        validate: {
            type: Function,
            default: null
        },
        onSubmit: {
            type: Function,
            default: null
        },
        submitOnChange: Boolean,
        validateOnMount: Boolean,
        enableReinitialize: Boolean,
    },
    watch: {
        initialValues: {
            deep: true,
            handler: function(values) {
                if (this.enableReinitialize) {
                    this.errors = {};
                    this.isValid = true;
                    this.isSubmitting = false;
                    this.values = {...this.values, ...values};
                }

                if (this.validateOnMount) {
                    this.handleValidate();
                }
             },
        },
        errors: {
            handler: function(values) {
               this.isValid = Object.keys(values).length < 1;
            },
        },
    },
    data() {
        return {
            values: {},
            errors: {},
            touched: {},
            isValid: true,
            isSubmitting: false
        }
    },
    methods: {
        handleBlur: function(e) {
            if (!this.touched[e.target.name])
                this.touched[e.target.name] = true;
        },
        handleChange: function(e) {
            if (e.target.name) {
                target = {};
                targetName = e.target.name.split('.');
                if (targetName.length == 1) {
                    target[targetName[0]] = e.target.value;
                } else {
                    target[targetName[0]] = {
                        [targetName[1]]: e.target.value
                    }
                }

                valName = Object.keys(target)[0];

                if (valName in this.values) {
                    let valValue = target[valName];
                    if (typeof valValue == 'object') {
                        valValue = Array.isArray(valValue)? valValue : {...this.values[valName], ...valValue};
                    }

                    if (e.target.type == 'checkbox') {
                        valValue = !this.values[valName]
                    }

                    this.setFieldValue(valName, valValue)
                }
            } else {
                this.handleValidate();
            }
        },
        handleSubmit: function() {
            if (!this.isValid) {
                return;
            }

            this.isSubmitting = true;

            if (this.onSubmit) {
                this.onSubmit(this.values, {
                    setSubmitting: this.setSubmitting
                });
            }
        },
        handleValidate: function() {
            this.errors = this.validate? this.validate(this.values) : {};
        },

        setSubmitting: function(isSubmitting) {
            this.isSubmitting = isSubmitting;
        },
        setFieldValue: function(field, value, shouldNotValidate) {
            this.values[field] = value;
            if (shouldNotValidate !== true) {
                this.handleValidate();
            }

            // todo: await validation
            setTimeout(() => {
                if (this.submitOnChange) {
                    this.handleSubmit();
                }
            }, 100)
        },
    },
    template: `
        <div>
            <slot
                :values="values"
                :errors="errors"

                :isValid="isValid"
                :isSubmitting="isSubmitting"

                :setFieldValue="setFieldValue"

                :handleBlur="handleBlur"
                :handleChange="handleChange"
                :handleSubmit="handleSubmit"
            >
                <form></form>
            </slot>
        </div>
    `,
    beforeMount() {
        this.values = {...this.initialValues};
        if (this.validateOnMount)
            this.handleValidate();
    }
})