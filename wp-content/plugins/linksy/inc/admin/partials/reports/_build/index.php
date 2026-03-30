<div class="container" id="reports-app" v-cloak>
  <linksy-tab-bar persist :tab="tab" :tabs="tabs" @on-select="handleTabSelect" @on-tab-mounted="handleTabMounted"></linksy-tab-bar>
  <linksy-tab :tab="tab" :tabs="tabs" v-slot="{currentTab}" v-if="tabsMounted">
      <internal-links-report v-if="currentTab.id == 'internal-links-report'"></internal-links-report>
      <domain-report v-if="currentTab.id == 'domain-report'"></domain-report>
  </linksy-tab>
</div>