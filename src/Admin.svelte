<script lang="ts">
  import { Button } from "$lib/ui/button/index.js";
  import * as Tabs from "$lib/ui/tabs/index.js";
  import { Github } from "@lucide/svelte";
  import General from "./admin-tabs/General.svelte";
  import TransferCosts from "./admin-tabs/TransferCosts.svelte";
  import BondCosts from "./admin-tabs/BondCosts.svelte";
  import PdfMailSettings from "./admin-tabs/PDFMailSettings.svelte";

  interface CalculatorItem {
    date: string;
    type: string;
    email: string;
    amount: number;
    fee: number;
  }

  interface WordPressResponse {
    success: boolean;
    data: CalculatorItem[];
  }

  interface PdfSettings {
    api_key: string;
    template_html: string;
  }

  interface WindowWithBCAdmin extends Window {
    bcAdmin: {
      apiUrl: string;
      nonce: string;
    };
  }

  let calculatorData = $state<CalculatorItem[]>([]);
  let transferCosts = $state([]);
  let bondCosts = $state([]);
  let pdfSettings = $state({
    api_key: "",
    template_html: "",
    sender_email: "",
    sender_name: "",
    subject_line: "",
  });
  let loading = $state(true);
  let error = $state("");

  async function loadData() {
    loading = true;
    error = "";
    try {
      const calculatorResponse = await fetch(
        `${window.bcAdmin.apiUrl}/calculator-data`,
        {
          headers: {
            "X-WP-Nonce": window.bcAdmin.nonce,
          },
        },
      );

      if (calculatorResponse.ok) {
        calculatorData = await calculatorResponse.json();
      }

      // Load transfer costs
      const transferResponse = await fetch(
        `${window.bcAdmin.apiUrl}/transfer-costs`,
        {
          headers: {
            "X-WP-Nonce": window.bcAdmin.nonce,
          },
        },
      );

      if (transferResponse.ok) {
        transferCosts = await transferResponse.json();
      }

      // Load bond costs
      const bondResponse = await fetch(`${window.bcAdmin.apiUrl}/bond-costs`, {
        headers: {
          "X-WP-Nonce": window.bcAdmin.nonce,
        },
      });

      if (bondResponse.ok) {
        bondCosts = await bondResponse.json();
      }

      // Load PDF settings
      const pdfResponse = await fetch(`${window.bcAdmin.apiUrl}/pdf-settings`, {
        headers: {
          "X-WP-Nonce": window.bcAdmin.nonce,
        },
      });

      if (pdfResponse.ok) {
        pdfSettings = await pdfResponse.json();
      }
    } catch (err) {
      error = err.message;
    } finally {
      loading = false;
    }
  }
  async function loadPdfSettings() {
    try {
      const response = await fetch(
        `${(window as WindowWithBCAdmin).bcAdmin.apiUrl}/pdf-settings`,
        {
          headers: {
            "X-WP-Nonce": (window as WindowWithBCAdmin).bcAdmin.nonce,
          },
        },
      );

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          pdfSettings = result.data;
        }
      }
    } catch (err) {
      console.error("Failed to load PDF settings:", err);
    }
  }

  loadPdfSettings();
  loadData();
</script>

<div class="p-6 bg-white">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Bond Calculator Dashboard</h1>
    <Button
      target="_blank"
      href="https://github.com/krugcode/wp-bond-calculator"
      class="text-white"
      size="icon"><Github /></Button
    >
  </div>

  <Tabs.Root value="general" class="w-full">
    <Tabs.List class="w-fit">
      <Tabs.Trigger value="general">General</Tabs.Trigger>
      <Tabs.Trigger value="transfer-costs">Transfer Costs</Tabs.Trigger>
      <Tabs.Trigger value="bond-costs">Bond Costs</Tabs.Trigger>
      <Tabs.Trigger value="pdf-settings">PDF & Mail Settings</Tabs.Trigger>
    </Tabs.List>

    {#if loading}
      <div class="flex items-center justify-center p-8">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
        ></div>
        <span class="ml-2 text-gray-600">Loading data...</span>
      </div>
    {:else if error}
      <div class="bg-red-50 border border-red-200 rounded-md p-4">
        <p class="text-red-800">Error: {error}</p>
      </div>
    {:else}
      <!-- General Tab  -->
      <Tabs.Content value="general">
        <General {calculatorData} />
      </Tabs.Content>

      <!-- Transfer Costs Tab -->
      <Tabs.Content value="transfer-costs">
        <TransferCosts {transferCosts} />
      </Tabs.Content>

      <!-- Bond Costs Tab -->
      <Tabs.Content value="bond-costs">
        <BondCosts {bondCosts} />
      </Tabs.Content>

      <Tabs.Content value="pdf-settings">
        <PdfMailSettings {pdfSettings} />
      </Tabs.Content>
    {/if}
  </Tabs.Root>
</div>
