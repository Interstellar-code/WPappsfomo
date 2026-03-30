const LinksyDropdown = ({
    name: 'LinksyDropdown',
    directives: {
        'click-outside': {
            beforeMount: (el, binding) => {
              el.clickOutsideEvent = event => {
                // here I check that click was outside the el and his children
                if (!(el == event.target || el.contains(event.target))) {
                  // and if it did, call method provided in attribute value
                  binding.value();
                }
              };
              document.addEventListener("click", el.clickOutsideEvent);
            },
            unmounted: el => {
              document.removeEventListener("click", el.clickOutsideEvent);
            },
        }
    },
    props: {
        loading: Boolean,
        hideIcon: Boolean,
        multiple: Boolean,
        disabled: Boolean,
        creatable: Boolean,
        resetable: Boolean,
        style: [String, Array, Object],
        placeholder: {
            type: String,
            default: 'Select'
        },
        icon: {
            type: String,
            default: 'chevron-down'
        },
        items: {
            type: Array,
            default: []
        },
        initialValue: {
            type: [String, Array, Object],
            default: null
        }
    },
    watch: {
        initialValue: {
            handler: function() {
                this.initialize();
            }
        }
    },
    data() {
        return {
            isActive: false,
            item: null,
            creatableItem: '',
            styles: {
                icon: {
                    fontSize: '14px'
                },
                button: {
                    fontSize: '12px',
                    padding: '4px 8px',
                    borderRadius: '4px',
                    background:' #EBEBEB',
                    marginLeft: '10px',
                    color: '#000000',
                    border: 'none',
                    whiteSpace: 'nowrap'
                }
            }
        }
    },
    computed: {
        title: {
            get() {
                if (this.multiple) {
                    if (!this.item || this.item.length < 1) {
                        return this.placeholder;
                    }

                    return `${this.item.length} Added`;
                }

                if (!this.item) {
                    return this.placeholder;
                }

                return this.getItemLabel(this.item);
            }
        },
    },
    methods: {
        initialize() {
            if (this.multiple) {
                this.item = [];
                // if (this.items.length > 0) {
                    //this.item.push(this.items[0]);
                // }
            } else {
                this.item = null
            }
    
            if (this.initialValue) {
                if (!this.multiple) {
                    this.item = this.initialValue;
                } else {
                    // todo: validate
                    this.item = [...this.initialValue];
                }
            }
        },
        toggleActiveState() {
            if (!this.disabled) {
                this.isActive = !this.isActive;
            }
        },
        getItemValue(item) {
            return typeof item == 'object'? item.value: item
        },
        getItemLabel(item) {
            return typeof item == 'object'? item.label: item;
        },
        onClear() {
            if (this.isActive) {
                this.toggleActiveState();
            }

            this.item = null;
            if (this.creatable) {
                this.creatableItem = '';
            }

            const _this = this;
            setTimeout(() => _this.$emit('selected', null), 10);
        },
        onOutsideClicked() {
            if (this.isActive) {
                this.toggleActiveState();
            }
        },
        onItemClicked(item) {
            if (!item) {
                return;
            }

            if (!this.multiple) {
                this.item = item;
                this.toggleActiveState();
            } else {
                const itemValue =  this.getItemValue(item);
                const itemIndex = this.item.findIndex(e => e === itemValue);
                if (itemIndex !== -1) {
                    this.item.splice(itemIndex, 1);
                } else {
                    this.item.push(itemValue);
                }
            }

            if (this.creatable) {
                this.creatableItem = '';
            }

            const _this = this;
            setTimeout(() => _this.$emit('selected', _this.multiple? Object.values(_this.item) : _this.item), 10);
        }
    },
    template: `
        <div ref="linksy-dropdown" class="linksy-dropdown" :class="{ creatable, multiple, disabled, 'no-icon' : hideIcon}" :style="[style]" v-click-outside="onOutsideClicked">
            <div class="linksy-dropdown-input" @click="toggleActiveState">
                <slot :title="title">
                    <span style="font-size: 16px; white-space: nowrap; padding: 0;">{{title}}</span>
                </slot>
                <div v-if="!hideIcon" style="padding-left: 5px;"><i class="fa" :class="'fa-'+icon" :style="styles.icon"></i></div>
            </div>
            <div v-if="isActive" class="linksy-dropdown-items-container shadow">
                <div v-if="resetable" @click="onClear" class="item clear">
                    <span style="color: #cecece;">{{placeholder || '--'}}</span>
                </div>
                <div @click="onItemClicked(e)" v-for="(e, i) in items" class="item" :class="{selected: item && ( item == getItemValue(e) || item.includes(getItemValue(e)) )}">
                    <span v-if="multiple" style="display:block;width:1rem;min-width:1rem;height:1rem;border:1px solid #000000;border-radius: 4px;vertical-align: middle;margin-right:0.55rem;text-align: center;padding-bottom: 0.1rem;">
                        <span>
                            <i :style="{opacity: item && item.includes(getItemValue(e))? '1' : '0'}" class="fa fa-check" style="color:#007AFF;"></i>
                        </span>
                    </span>
                    <slot name="item" :item="e" :index="i">
                        <span style="max-width: 400px; white-space: normal;">{{getItemLabel(e)}}</span>
                    </slot>
                </div>
                

                <div v-if="creatable" style="padding: 0 5px 8px;">
                    <hr />
                    <textarea style="width: 100%;min-width: 350px; min-height: 100px;" type="text" v-model="creatableItem" v-on:keyup.enter="onItemClicked(creatableItem)"></textarea>
                    <div style="margin-top: 5px; display: flex; justify-content: flex-end;">
                        <button :style="styles.button" @click="toggleActiveState"><i class="fa fa-times"></i>&nbsp;Cancel</button>
                        <button
                            @click="onItemClicked(creatableItem)"
                            :disabled="!creatableItem || creatableItem.length < 1"
                            :style="[styles.button, {background: '#007AFF', color: 'white'}]"
                        >
                            <i class="fa fa-check"></i>&nbsp;Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `,
    beforeMount() {
        this.initialize();
    }
});