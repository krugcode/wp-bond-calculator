<script lang="ts">
  import { Button } from "$lib/ui/button";
  import { FileDown } from "@lucide/svelte";

  let { calculatorData } = $props();
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
    <h3 class="text-lg font-medium text-gray-900">Recent Quote Mails</h3>
    <Button variant="secondary">Export to CSV <FileDown /></Button>
  </div>
</div>
