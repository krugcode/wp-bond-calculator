<script lang="ts">
  import Button from "$lib/ui/button/button.svelte";
  import * as Tabs from "$lib/ui/tabs/index";
  import { FileDown, Github, Save, Download, Eye } from "@lucide/svelte";
  import { onMount } from "svelte";

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
  let loading = $state(true);
  let error = $state<string | null>(null);

  // PDF Settings state
  let pdfSettings = $state<PdfSettings>({
    api_key: "",
    template_html: "",
  });
  let pdfLoading = $state(false);
  let pdfSaving = $state(false);
  let pdfMessage = $state("");

  onMount(() => {
    loadCalculatorData();
    loadPdfSettings();
  });

  async function loadCalculatorData() {
    try {
      const response = await fetch(
        `${(window as WindowWithBCAdmin).bcAdmin.apiUrl}/calculator-data`,
        {
          headers: {
            "X-WP-Nonce": (window as WindowWithBCAdmin).bcAdmin.nonce,
          },
        },
      );

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result: WordPressResponse = await response.json();

      if (result.success) {
        calculatorData = result.data;
      } else {
        throw new Error("API returned success: false");
      }
    } catch (err) {
      error = err instanceof Error ? err.message : "Unknown error occurred";
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

  function handleSavePdfSettings() {
    savePdfSettings();
  }

  async function savePdfSettings() {
    pdfSaving = true;
    pdfMessage = "";

    try {
      const response = await fetch(
        `${(window as WindowWithBCAdmin).bcAdmin.apiUrl}/pdf-settings`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": (window as WindowWithBCAdmin).bcAdmin.nonce,
          },
          body: JSON.stringify(pdfSettings),
        },
      );

      const result = await response.json();
      if (result.success) {
        pdfMessage = "Settings saved successfully!";
      } else {
        throw new Error(result.message || "Failed to save settings");
      }
    } catch (err) {
      pdfMessage = `Error: ${err instanceof Error ? err.message : "Unknown error"}`;
    } finally {
      pdfSaving = false;
    }
  }

  function handleDownloadExample() {
    downloadExample();
  }

  async function downloadExample() {
    pdfLoading = true;
    pdfMessage = "";

    try {
      const response = await fetch(
        `${(window as WindowWithBCAdmin).bcAdmin.apiUrl}/pdf-example`,
        {
          headers: {
            "X-WP-Nonce": (window as WindowWithBCAdmin).bcAdmin.nonce,
          },
        },
      );

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = "bond-calculator-example.pdf";
        a.click();
        window.URL.revokeObjectURL(url);
      } else {
        throw new Error("Failed to generate example PDF");
      }
    } catch (err) {
      pdfMessage = `Error: ${err instanceof Error ? err.message : "Unknown error"}`;
    } finally {
      pdfLoading = false;
    }
  }

  function handlePreviewTemplate() {
    previewTemplate();
  }

  async function previewTemplate() {
    if (!pdfSettings.template_html.trim()) {
      pdfMessage = "Please enter a template first";
      return;
    }

    // Create sample data for preview
    const sampleData: Record<string, string> = {
      transfer_amount: "R300,000.00",
      bond_amount: "R4,000,000.00",
      attorney_fee: "R10,880.00",
      total_fee: "R89,191.30",
      date: new Date().toLocaleDateString("en-GB"),
      government_costs: "R0.00",
      deeds_office_fee: "R721.00",
      to_transaction_fee: "R200.00",
      electronic_doc_fee: "R200.00",
      rates_clearance_fee: "R350.00",
      electronic_rates_fee: "R442.00",
      deeds_search_fee: "R259.00",
      fica_verification_fee: "R500.00",
      post_petties: "R2,000.00",
      vat_amount: "R2,233.30",
      sub_total: "R17,772.30",
    };

    // Replace placeholders with sample data
    let previewHtml = pdfSettings.template_html;
    Object.entries(sampleData).forEach(([key, value]) => {
      previewHtml = previewHtml.replace(new RegExp(`{{${key}}}`, "g"), value);
    });

    const previewWindow = window.open("", "_blank");
    if (previewWindow) {
      previewWindow.document.write(previewHtml);
      previewWindow.document.close();
    }
  }

  const totalCalculations = $derived(calculatorData.length);
  const averageAmount = $derived(
    calculatorData.length > 0
      ? Math.round(
          calculatorData.reduce((sum, item) => sum + item.amount, 0) /
            calculatorData.length,
        )
      : 0,
  );
  const averageFees = $derived(
    calculatorData.length > 0
      ? Math.round(
          calculatorData.reduce((sum, item) => sum + item.fee, 0) /
            calculatorData.length,
        )
      : 0,
  );
</script>

<div class="p-6 bg-white">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Bond Calculator Dashboard</h1>
    <Button size="icon"><Github /></Button>
  </div>

  <Tabs.Root value="general" class="w-full">
    <Tabs.List class="w-fit">
      <Tabs.Trigger value="general">General</Tabs.Trigger>
      <Tabs.Trigger value="transfer-bond-calculator">Transfer Bond</Tabs.Trigger
      >
      <Tabs.Trigger value="bond-repayment-calculator"
        >Bond Repayment</Tabs.Trigger
      >
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
      <Tabs.Content value="general">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div class="bg-blue-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-blue-600">Quotes Generated</h3>
            <p class="text-2xl font-bold text-blue-900">{totalCalculations}</p>
          </div>
          <div class="bg-green-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-green-600">Average Amount</h3>
            <p class="text-2xl font-bold text-green-900">
              R{averageAmount.toLocaleString()}
            </p>
          </div>
          <div class="bg-purple-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-purple-600">Average Fees</h3>
            <p class="text-2xl font-bold text-purple-900">
              R{averageFees.toLocaleString()}
            </p>
          </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
          <div
            class="flex justify-between items-center px-6 py-4 border-b border-gray-200"
          >
            <h3 class="text-lg font-medium text-gray-900">
              Recent Quote Mails
            </h3>
            <Button variant="secondary">Export to CSV <FileDown /></Button>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >Date</th
                  >
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >Type</th
                  >
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >Email</th
                  >
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >Amount</th
                  >
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >Fee</th
                  >
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                {#each calculatorData as item}
                  <tr>
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                      >{item.date}</td
                    >
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
                      >
                        {item.type}
                      </span>
                    </td>
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                      >{item.email}</td
                    >
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                      >R{item.amount.toLocaleString()}</td
                    >
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                      >R{item.fee.toLocaleString()}</td
                    >
                  </tr>
                {/each}
              </tbody>
            </table>
            {#if calculatorData.length === 0}
              <div class="p-8 text-center text-gray-500">
                No calculations yet.
              </div>
            {/if}
          </div>
        </div>
      </Tabs.Content>

      <Tabs.Content value="transfer-bond-calculator">
        Transfer Bond Calculator
      </Tabs.Content>

      <Tabs.Content value="bond-repayment-calculator">
        Bond Repayment Calculator
      </Tabs.Content>

      <Tabs.Content value="pdf-settings">
        <div class="max-w-4xl space-y-6">
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              PDF Generation Settings
            </h3>

            {#if pdfMessage}
              <div
                class="mb-4 p-3 rounded-md {pdfMessage.startsWith('Error')
                  ? 'bg-red-50 text-red-800 border border-red-200'
                  : 'bg-green-50 text-green-800 border border-green-200'}"
              >
                {pdfMessage}
              </div>
            {/if}

            <div class="space-y-4">
              <!-- API Key Input -->
              <div>
                <label
                  for="api-key"
                  class="block text-sm font-medium text-gray-700 mb-2"
                >
                  API2PDF API Key
                </label>
                <input
                  id="api-key"
                  type="password"
                  bind:value={pdfSettings.api_key}
                  placeholder="Enter your API2PDF API key"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <p class="mt-1 text-xs text-gray-500">
                  Get your API key from <a
                    href="https://portal.api2pdf.com"
                    target="_blank"
                    class="text-blue-600 hover:text-blue-800"
                    >portal.api2pdf.com</a
                  >
                </p>
              </div>

              <!-- Template Editor -->
              <div>
                <label
                  for="template"
                  class="block text-sm font-medium text-gray-700 mb-2"
                >
                  PDF Template (HTML)
                </label>
                <!-- <textarea -->
                <!--   id="template" -->
                <!--   bind:value={pdfSettings.template_html} -->
                <!--   rows="15" -->
                <!--   placeholder="Enter your HTML template with placeholders like {{ -->
                <!--     transfer_amount, -->
                <!--   }}, {{ total_fee }}, etc." -->
                <!--   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm" -->
                <!-- ></textarea> -->
                <!-- <p class="mt-1 text-xs text-gray-500"> -->
                <!--   Use placeholders like {{ transfer_amount }}, {{ -->
                <!--     bond_amount, -->
                <!--   }}, {{ attorney_fee }}, {{ total_fee }}, {{ date }}, etc. -->
                <!-- </p> -->
              </div>

              <!-- Action Buttons -->
              <div class="flex space-x-3">
                <Button
                  onclick={handleSavePdfSettings}
                  disabled={pdfSaving}
                  variant="default"
                >
                  {#if pdfSaving}
                    Saving...
                  {:else}
                    <Save class="w-4 h-4 mr-2" />
                    Save Settings
                  {/if}
                </Button>

                <Button
                  onclick={handlePreviewTemplate}
                  variant="outline"
                  disabled={!pdfSettings.template_html.trim()}
                >
                  <Eye class="w-4 h-4 mr-2" />
                  Preview Template
                </Button>

                <Button
                  onclick={handleDownloadExample}
                  disabled={pdfLoading || !pdfSettings.api_key.trim()}
                  variant="secondary"
                >
                  {#if pdfLoading}
                    Generating...
                  {:else}
                    <Download class="w-4 h-4 mr-2" />
                    Download Example PDF
                  {/if}
                </Button>
              </div>
            </div>
          </div>
        </div>
      </Tabs.Content>
    {/if}
  </Tabs.Root>
</div>
