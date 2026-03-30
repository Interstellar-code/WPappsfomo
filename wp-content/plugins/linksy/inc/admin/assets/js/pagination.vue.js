const LinksyPagination = ({
    name: 'LinksyPagination',
    props: {
        loading: Boolean,
        perPage: {
            type: [Number, String],
            default: 10
        },
        perPageOptions: {
            type: Array,
            default: [5, 10, 20, 30, 50]
        },
        pageLength: [Number, String],
        currentPage: [Number, String],
        total: [Number, String],
     },
    data() {
        return {
            length: null,
        }
    },
    computed: {
        paginationFrom: {
            get() {
                if (this.currentPage) {
                    return ((this.currentPage - 1) * this.perPage) + 1;
                }

                return 0;
            }
        },
        paginationTo: {
            get() {
                if (this.currentPage) {
                    return  ((this.currentPage - 1) * this.perPage) + Math.min(this.pageLength, this.total);
                }

                return 0;
            }
        },
        paginationLastPage: {
            get() {
                let total = 0;
                if (this.total && this.perPage){
                    total = Math.ceil(this.total / this.perPage) + 1;
                }
                return  total;
            },
        },
        paginationPrevPage: {
            get() {
                if (this.currentPage) {
                    return this.currentPage - 1;
                }
                return 0;
            },
        },
        paginationNextPage: {
            get() {
                if (this.currentPage) {
                    return this.currentPage + 1;
                }
                return 0;
            },
        },
    },
    methods: {
        onChangePage(page) {
            console.log(page)
            this.$emit('onChangePage', page);
        },

        onChangeRowsPerPage() {
            console.log(this.length)
            this.$emit('onChangeRowsPerPage', parseInt(this.length))
        }
    },
    template: `
    <div class="linksy-pagination">
        <span>Rows per page</span>
        <div class="length">
            <span>{{length}}</span>
            <select v-model="length" @change="onChangeRowsPerPage">
                <option v-for="item in perPageOptions">{{item}}</option>
            </select>
            <button><i class="fa fa-angle-down"></i></button>
        </div>
        <span v-if="paginationFrom && paginationTo">{{paginationFrom}}-{{paginationTo}} / {{total}}</span>
        <div class="navs">
            <button :disabled="paginationPrevPage < 1" @click="onChangePage(1)">
                <i class="fa fa-angles-left"></i>
            </button>
            <button :disabled="paginationPrevPage < 1" @click="onChangePage(paginationPrevPage)">
                <i class="fa fa-angle-left"></i>
            </button>
            <button :disabled="paginationNextPage >= paginationLastPage" @click="onChangePage(paginationNextPage)">
                <i class="fa fa-angle-right"></i>
            </button>
            <button :disabled="paginationNextPage >= paginationLastPage" @click="onChangePage(paginationLastPage -1)">
                <i class="fa fa-angles-right"></i>
            </button>
        </div>
    </div>
    `,
    beforeMount() {
        this.length = this.perPage;
    },
});