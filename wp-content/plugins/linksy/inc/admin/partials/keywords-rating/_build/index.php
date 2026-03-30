<div class="container with-content" id="keywords-rating-app" v-cloak>
    <div class="title-container">
        <h2 class="title">Keyword Rating</h2>

        <span class="sub-title">
          This page displays the keywords the pages on your site are targeting.
        </span>
    </div>

    <div class="search">
        <div class="d-flex">
            <linksy-search cancelable class="input-container" @on-change="handleSearch"></linksy-search>
        </div>

        <div class="actions">
            <button @click="resetKeywords">Refresh</button>
            <linksy-export :loading="xport.loading" :columns="['Page Title', 'Page URL', 'Post Type', 'Category', 'Pub. Date', 'Target Keywords', showRatingFilter? 'Rating' : null]" @on-export="handleExport">
                <div v-if="xport.loading" class="loader">
                    <span>
                        <i class="fa fa-spin fa-spinner"></i>
                    </span>
                </div>
                <template v-slot:actions="{disabled, columns}">
                    <button class="btn-link"  @click="handleExport(columns)">
                        Basic Export
                    </button>
                    <button class="btn-link" @click="handleExport(columns, true)">
                        Detailed Export
                    </button>
                </template>
            </linksy-export>
        </div>
    </div>
    
    <br />

    <linksy-form
        class="filters"
        enable-reinitialize
        :on-submit="handleFilterChange"
        :initial-values="filter"
        :validate="validateFilters"
        v-slot="{
            values,
            errors,
            handleChange,
            handleSubmit,
            setFieldValue,
        }"
    >
        <div>
            <span>Type</span>
            <select name="type" :class="{'invalid': errors.type}" @change="e => {
                setFieldValue('category', [], true);
                handleChange(e);
            }">
                <option value="">All</option>
                <option :value="type" v-for="(type, index) in types" :selected="values.type == type">
                    {{type}}
                </option>
            </select>
        </div>
        <div>
            <span>Category</span>
            <linksy-dropdown
                resetable
                multiple
                key="dropdown"
                placeholder="All"
                :initial-value="values.category"
                :items="values.type == 'page' ? [] : categories"
                @selected="(e) => setFieldValue('category', e, true)"
            >
            </linksy-dropdown>
        </div>
        <div>
            <span>Published Date</span>
            <input type="text" class="daterangepickerinput"  placeholder="All Dates" readonly/>
        </div>
        <div>
            <span>Rating</span>
            <linksy-dropdown
                style="min-width: 120px"
                key="dropdown"
                resetable
                placeholder="All"
                :disabled="!showRatingFilter"
                :initial-value="values.rating"
                :items="['Great','Good','Average','Poor', 'No Keyword']"
                @selected="(e) => setFieldValue('rating', e, true)"
            >
            </linksy-dropdown>
        </div>
        <div>
            <span>Keyword Contain</span>
            <input type="text" name="keyword" @input="handleChange" style="max-width: 200px;"/>
        </div>
        <div>
            <span>&nbsp;</span>
            <div class="d-flex">
                <button class="btn-default" @click="handleFilterReset"><i class="fa fa-arrows-rotate"></i></button>
                <span>&nbsp;</span>
                <button class="btn-app" @click="handleSubmit"><i class="fa fa-arrow-down-wide-short"></i>Filter</button>
            </div>
        </div>
    </linksy-form>

    <br />

    <linksy-data-table
        :data="items"
        :columns="columns"
        :loading="isLoading"
        @on-sort="handleSort"
    >
        <template v-slot:column-header-0>
            <div>Page Title <span v-if="!isLoading">({{pagination.total}})</span></div>
        </template>

        <template v-slot:column-header-1="{item, index}">
          <div class="custom">
            <span>{{item.name}}</span>
            <div style="display: flex; margin-left: 20px;">
              <button class="font-weight-light" :class="{active: mode == 'int'}" @click.stop="handleModeChange('int')">INTERNAL</button>
              <button class="font-weight-light" :class="{active: mode == 'cus'}" @click.stop="handleModeChange('cus')">CUSTOM</button>
            </div>
          </div>
        </template>

        <template v-slot:column-0="{item, index}">
            <div class="page-title">
                <span>{{item.title}}</span>
                <div>
                    <a :href="item.inbound_link" target="_blank">Link In</a>
                    <a :href="item.edit_link" target="_blank">Edit/Link Out</a>
                    <a :href="item.link" target="_blank">View</a> 
                </div>
            </div>
        </template>

        <template v-slot:column-1="{item, index}">
            <keywords 
                :mode="mode"
                :id="item.post_id" 
                :items="item.keywords"
                @on-add-custom-keywords="addCustomKeywords"
                @on-delete-custom-keyword="removeCustomKeyword"
            >
            </keywords>
        </template>
    </linksy-data-table>

    <br />

    <linksy-pagination
        :page-length="items.length"
        :per-page="pagination.length"
        :current-page="pagination.page+1"
        :total="pagination.total"
        @on-change-page="handleChangePage"
        @on-change-rows-per-page="handleChangeRowsPerPage"
    ></linksy-pagination>
</div>

