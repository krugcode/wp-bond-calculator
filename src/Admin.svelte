<script lang="ts">
  import Button from "$lib/ui/button/button.svelte";
  import { onMount } from "svelte";

  interface CalculatorItem {
    id: number;
    type: string;
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

  function addSampleData(): void {
    calculatorData = [
      ...calculatorData,
      {
        id: Date.now(),
        type: "transfer",
        amount: Math.floor(Math.random() * 1000000) + 100000,
        fee: Math.floor(Math.random() * 10000) + 1000,
      },
    ];
  }

  // Derived values using Svelte 5 $derived
  const totalCalculations = $derived(calculatorData.length);
  const averageAmount = $derived(
    calculatorData.length > 0
      ? Math.round(
          calculatorData.reduce((sum, item) => sum + item.amount, 0) /
            calculatorData.length,
        )
      : 0,
  );
  const totalFees = $derived(
    calculatorData.reduce((sum, item) => sum + item.fee, 0),
  );
</script>

<div class="p-6 bg-white">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Bond Calculator Dashboard</h1>
    <Button>Add Sample Data</Button>
    <button
      onclick={addSampleData}
      class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
    >
      Add Sample Data
    </button>
  </div>

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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-blue-50 p-4 rounded-lg">
        <h3 class="text-sm font-medium text-blue-600">Total Calculations</h3>
        <p class="text-2xl font-bold text-blue-900">{totalCalculations}</p>
      </div>
      <div class="bg-green-50 p-4 rounded-lg">
        <h3 class="text-sm font-medium text-green-600">Average Amount</h3>
        <p class="text-2xl font-bold text-green-900">
          R{averageAmount.toLocaleString()}
        </p>
      </div>
      <div class="bg-purple-50 p-4 rounded-lg">
        <h3 class="text-sm font-medium text-purple-600">Total Fees</h3>
        <p class="text-2xl font-bold text-purple-900">
          R{totalFees.toLocaleString()}
        </p>
      </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Recent Calculations</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >ID</th
              >
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >Type</th
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
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                  >{item.id}</td
                >
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
                  >
                    {item.type}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                  >R{item.amount.toLocaleString()}</td
                >
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                  >R{item.fee.toLocaleString()}</td
                >
              </tr>
            {/each}
          </tbody>
        </table>
        {#if calculatorData.length === 0}
          <div class="p-8 text-center text-gray-500">
            No calculations yet. Click "Add Sample Data" to get started.
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div>
