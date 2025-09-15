<script lang="ts">
  import Button from "$lib/ui/button/button.svelte";
  import * as Tabs from "$lib/ui/tabs/index";
  import { FileDown, Github } from "@lucide/svelte";
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

  interface WindowWithBCAdmin extends Window {
    bcAdmin: {
      apiUrl: string;
      nonce: string;
    };
  }

  let calculatorData = $state<CalculatorItem[]>([]);
  let loading = $state(true);
  let error = $state<string | null>(null);

  onMount(async () => {
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
  });

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
      <Tabs.Content value="general"
        ><div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
        Transfer Bond Calulator
        <!-- Shortcode -->
        <!-- File upload to upload the CSV of pricing -->
        <!-- Download to download the current prices as CSVs -->
      </Tabs.Content>

      <Tabs.Content value="bond-repayment-calculator">
        Bond Repayment Calculator
        <!-- Shortcode -->
        <!-- File upload to upload the CSV of pricing -->
        <!-- Download to download the current prices as CSVs -->
      </Tabs.Content>
      <Tabs.Content value="pdf-settings">
        <!-- PDF settings -->
        <!-- SMTP/Wordpress mailer settings (user can choose)-->
      </Tabs.Content>
    {/if}
  </Tabs.Root>
</div>
