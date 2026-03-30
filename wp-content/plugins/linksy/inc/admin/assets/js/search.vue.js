const LinksySearch = ({
    name: 'LinksySearch',
    props: {
        style: [Object, Array, String],
        placeholder: {
            type: String,
            default: 'Search'
        },
        icon: {
            type: String,
            default: 'magnifying-glass'
        },
        initialValue: String,
        loading: Boolean,
        predict: Boolean,
        cancelable: Boolean,
        predictions: {
            type: Array,
            default: []
        },
     },
    data() {
        return {
            q: '',
            styles: {
                container: {
                    padding: '4px 12px',
                    borderRadius: '5px',
                    position: 'relative',
                },
                icon: {
                    fontSize: '18px'
                },
                input: {
                    flex: 1,
                    width: '100%',
                    border: 'none',
                    outline: 'none',
                    fontSize: '16px',
                    paddingLeft: '15px',
                    boxShadow: 'none',
                    background: 'transparent',
                },
                inputFirst: {
                    paddingLeft: '0',
                    paddingRight: '15px',
                },
                predictContainer: {
                    left: 0,
                    right: 0,
                    zIndex: 9,
                    top: '40px',
                    padding: '5px',
                    maxHeight: '300px',
                    position: 'absolute',
                    background: '#FFFFFF',
                    overflowY: 'scroll',
                    borderBottomLeftRadius: '5px',
                    borderBottomRightRadius: '5px'
                },
                predictItem: {
                    cursor: 'pointer',
                    padding: '8px 10px',
                    borderBottom: '1px solid #FEFEFE',
                }
            }
        }
    },
    computed: {
        currentPredictions: {
            get() {
                return this.predictions.filter(e => e.toLowerCase().includes(this.q.toLowerCase()) && e.toLowerCase() !== this.q.toLowerCase());
            },
        },
        showDropdown: {
            get() {
                return this.predict && this.q.length > 1;
            },
        },
    },
    watch: {
        initialValue: function (val) {
            if (val != this.q) {
                this.q = val;
            }
        },
        q: debounce(function (val) {
            if (!this.predict)
                this.$emit('onChange', val)
        }, 900),
    },
    methods: {
        onCancel() {
            this.q = '';
            this.$emit('onChange', '')
        },

        onPredictionClicked(val) {
            this.q = val;
            this.$emit('onChange', val, this.predictions.findIndex(e => e === val))
        }
    },
    template: `
        <div class="linksy-search searchbar" :class="{cancelable}" :style="[styles.container, style]">
            <div style="display:flex;align-items:center;justify-content:space-between;width:100%;">
                <i class="fa" :class="'fa-'+icon" :style="styles.icon"></i>
                <input
                    type="text"
                    v-model="q"
                    :style="[styles.input]"
                    :placeholder="placeholder"
                />
                <i v-if="cancelable && q" class="fa fa-times cancelable-icon" :style="styles.icon" @click="onCancel"></i>
            </div>
            <div v-if="showDropdown && currentPredictions.length > 0" class="shadow" :style="styles.predictContainer">
                <div @click="onPredictionClicked(prediction)" v-for="(prediction, predictionIndex) in currentPredictions" :style="styles.predictItem">
                    <span>{{prediction}}</span>
                </div>
            </div>
        </div>
    `,
    beforeMount() {
        if (this.initialValue) {
            this.q = initialValue;
        }
    }
})

/** @deprcated */
Search = LinksySearch;