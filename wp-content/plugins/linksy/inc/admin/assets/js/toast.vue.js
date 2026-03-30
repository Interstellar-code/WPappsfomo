const {h, defineComponent, render} = Vue;

function removeElement(el) {
    if (typeof el.remove !== 'undefined') {
      el.remove()
    } else {
      el.parentNode?.removeChild(el)
    }
}

function createComponent(component, props, parentContainer, slots = {}) {
    const vNode = h(component, props, slots)
    const container = document.createElement('div');
    container.classList.add('toast-pending')
    parentContainer.appendChild(container);
    render(vNode, container);
  
    return vNode.component
}

let eventBus = new EventBus();

let LinksyToastComponent = defineComponent({
    name: 'LinksyToast',
    props: {
        message: {
            type: String,
            required: true
        },
        icon: String,
        type: {
            type: String,
            default: 'default'
        },
        position: {
            type: String,
            default: 'bottom-right',
            validator(value) {
                return ['bottom-left', 'bottom-right'].includes(value)
            }
        },
        duration: {
            type: Number,
            default: 3000
        },
        dismissible: Boolean,



        onClick: {
            type: Function,
            default: () => {}
        },
        onDismiss: {
            type: Function,
            default: () => {}
        },
    },
    data() {
        return {
            parent: null,
            isActive: false,
            isHovered: false,
        }
    },
    beforeMount() {
        this.setupContainer()
    },
    mounted() {
        this.showNotice();
        eventBus.on('toast-clear', this.dismiss)
    },
    methods: {
        setupContainer() {
            this.parent = document.querySelector('.linksy-toast-container');
        },

        showNotice() {
            const wrapper = this.$refs.root.parentElement
            this.parent.insertAdjacentElement('afterbegin', this.$refs.root);
            removeElement(wrapper);
            this.isActive = true;
            if (this.duration) {
                this.timer = new Timer(this.dismiss, this.duration);
            }
        },
 
        dismiss() {
            if (this.timer) {
                this.timer.stop();
            }
            this.isActive = false;
            setTimeout(() => {
                this.onDismiss.apply(null, arguments);
                const wrapper = this.$refs.root;
                // unmount the component
                render(null, wrapper);
                removeElement(wrapper)
            }, 150)
        },


        onHover(newVal) {
            if (!this.pauseOnHover || !this.timer){ 
                return;
            }
            newVal ? this.timer.pause() : this.timer.resume();
        },

        onClicked() {
            this.onClick.apply(null, arguments);

            if (this.dismissible) {
                this.dismiss()
            }
        }
    },
    template: `
        <transition>
            <div
                ref="root"
                v-show="isActive"
                class="toast linksy-toast"
                :class="['toast-'+type, 'toast-'+position, {'dissmisable': dismissible}]"
                @mouseover="onHover(true)"
                @mouseleave="onHover(false)"
                @click="onClicked"
            >
                <div v-if="icon" class="toast-icon">
                    <i class="fa" :class="'fa-'+icon"></i>
                </div>
                <slot :message="message">
                    <p class="toast-text" v-html="message"></p>
                </slot>
                <button v-if="dismissible" class="close-btn" @click="dismiss">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </transition>
    `,
    beforeUnmount() {
        eventBus.off('toast-clear', this.dismiss)
    }
})

const useToast = (globalProps = {}) => {
    return {
      open(options) {
        if (typeof options === 'string') {
            let message = options;
            options = {
                message
            };
        };
  
        const propsData = Object.assign({}, globalProps, options);
  
        const instance = createComponent(LinksyToastComponent, propsData, document.body);
  
        return {
          dismiss: instance.ctx.dismiss
        }
      },
      clear() {
        eventBus.emit('toast-clear')
      },
      success(message, options = {}) {
        return this.open(Object.assign({}, {
          message,
          icon: 'circle-check',
          type: 'success'
        }, options))
      },
      error(message, options = {}) {
        return this.open(Object.assign({}, {
          message,
          icon: 'triangle-exclamation',
          type: 'error'
        }, options))
      },
      info(message, options = {}) {
        return this.open(Object.assign({}, {
          message,
          icon: 'circle-info',
          type: 'info'
        }, options))
      },
      warning(message, options = {}) {
        return this.open(Object.assign({}, {
          message,
          icon: 'circle-exclamation',
          type: 'warning'
        }, options))
      },
      default(message, options = {}) {
        return this.open(Object.assign({}, {
          message,
          icon: 'circle-info',
          type: 'default'
        }, options))
      }
    }
};

const LinksyToast = {
    install: (app, options = {}) => {
        // create container 
        let toastContainer = document.createElement('div');
        toastContainer.className = 'linksy-toast-container';
        document.body.appendChild(toastContainer);

        let instance = useToast(options);
        app.config.globalProperties.$toast = instance;
        app.provide('$toast', instance)
    }
}
