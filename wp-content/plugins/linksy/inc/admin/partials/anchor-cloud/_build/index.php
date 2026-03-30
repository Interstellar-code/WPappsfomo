<div class="container with-content" id="anchor-cloud-app" v-cloak>
    <div class="title-container">
        <h2 class="title">
          Anchors Cloud
        </h2>

        <span class="sub-title">This page displays all the anchors pointing to internal pages on your site.</span>
    </div>

    <div class="search">
        <div style="display: flex;">
            <div style="display: flex;">
                <linksy-search cancelable placeholder="Search Anchors" class="input-container" @on-change="handleSearch"></linksy-search>
            </div>
        </div>

        <div class="actions">
            <linksy-export :columns="['Anchor', 'Target', 'Target URL', showRatingFilter? 'Target Score' : null, 'Source', 'Source URL']" @on-export="handleExport">
                <template v-slot:actions="{disabled, columns}">
                    <button class="btn-link py-0" style="height: auto;"  @click="handleExport(columns)">
                        Export
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
            <span>Pub. Date</span>
            <input type="text" class="daterangepickerinput" placeholder="All Dates" readonly/>
        </div>
        <div class="rating">
            <span>Rating</span>
            <linksy-dropdown
                resetable
                key="dropdown"
                placeholder="All"
                :disabled="!showRatingFilter"
                :initial-value="values.rating"
                :items="['Great','Good','Average','Poor']"
                @selected="(e) => setFieldValue('rating', e, true)"
            >
            </linksy-dropdown>
        </div>
        <div>
            <span>Duplicate</span>
            <div class="custom" :class="{'invalid': errors.duplicate}">
                <input type="number" min="0" name="duplicate.min" :value="values.duplicate.min"  @input="handleChange"/>
                <input type="number" min="1" name="duplicate.max" :value="values.duplicate.max" @input="handleChange"/>
            </div>
        </div>
        <div>
            <span>Anchor Length</span>
            <div class="custom" :class="{'invalid': errors.anchorLength}">
                <input type="number" min="0" name="anchorLength.min" :value="values.anchorLength.min" @input="handleChange"/>
                <input type="number" min="1" name="anchorLength.max" :value="values.anchorLength.max" @input="handleChange"/>
            </div>
        </div>
        <div>
            <span>Target Contains</span>
            <input type="text" name="target" :value="values.target" @input="handleChange"/>
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
            <div>Anchors <span v-if="!isLoading">({{pagination.total}})</span></div>
        </template>

        <template v-slot:column-1="{item, index}">
            <occurrences :item="item"></occurrences>
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