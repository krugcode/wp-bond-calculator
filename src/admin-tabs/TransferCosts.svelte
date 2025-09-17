<script lang="ts">
  import { Button } from "$lib/ui/button";
  import { AlertCircle, CheckCircle, FileDown, Upload } from "@lucide/svelte";

  let { transferCosts } = $props();
  let transferFileInput;
  let transferUploading = $state(false);
  let transferUploadMessage = $state("");
  async function downloadTransferCosts() {
    try {
      const response = await fetch(
        `${window.bcAdmin.apiUrl}/download-transfer-costs`,
        {
          headers: {
            "X-WP-Nonce": window.bcAdmin.nonce,
          },
        },
      );

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = "transfer-costs.csv";
        a.click();
        window.URL.revokeObjectURL(url);
      }
    } catch (err) {
      transferUploadMessage = `Download Error: ${err.message}`;
    }
  }
  async function handleTransferUpload() {
    const file = transferFileInput.files[0];
    if (!file) {
      transferUploadMessage = "Please select a CSV file";
      return;
    }

    if (!file.name.toLowerCase().endsWith(".csv")) {
      transferUploadMessage = "Please select a valid CSV file";
      return;
    }

    transferUploading = true;
    transferUploadMessage = "";

    try {
      const formData = new FormData();
      formData.append("csv_file", file);

      const response = await fetch(
        `${window.bcAdmin.apiUrl}/upload-transfer-costs`,
        {
          method: "POST",
          headers: {
            "X-WP-Nonce": window.bcAdmin.nonce,
          },
          body: formData,
        },
      );

      const result = await response.json();

      if (response.ok) {
        transferUploadMessage = `Success: Uploaded ${result.count} transfer cost entries`;
        transferCosts = result.data;
        transferFileInput.value = ""; // Clear file input
      } else {
        transferUploadMessage = `Error: ${result.message}`;
      }
    } catch (err) {
      transferUploadMessage = `Error: ${err.message}`;
    } finally {
      transferUploading = false;
    }
  }
</script>

<div class="max-w-6xl space-y-6">
  <div>
    <h3 class="text-lg font-medium text-gray-900 mb-4">
      Transfer Cost Management
    </h3>

    <!-- Upload/Download Controls -->
    <div class="bg-gray-50 p-6 rounded-lg mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="w-full flex flex-col gap-1">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Upload Transfer Costs CSV
          </label>
          <div class="flex items-center w-full space-x-2">
            <input
              bind:this={transferFileInput}
              type="file"
              accept=".csv"
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            <Button
              onclick={handleTransferUpload}
              disabled={transferUploading}
              size="sm"
            >
              {#if transferUploading}
                <div
                  class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"
                ></div>
              {:else}
                <Upload class="w-4 h-4 mr-2" />
              {/if}
              Upload
            </Button>
          </div>

          {#if transferUploadMessage}
            <div
              class="mt-2 p-3 rounded-md {transferUploadMessage.startsWith(
                'Success',
              )
                ? 'bg-green-50 text-green-800'
                : 'bg-red-50 text-red-800'}"
            >
              {#if transferUploadMessage.startsWith("Success")}
                <CheckCircle class="w-4 h-4 inline mr-2" />
              {:else}
                <AlertCircle class="w-4 h-4 inline mr-2" />
              {/if}
              {transferUploadMessage}
            </div>
          {/if}
        </div>
      </div>
    </div>

    <!-- Current Costs Table -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
      <div
        class="flex items-center px-6 py-4 justify-between border-b border-gray-200"
      >
        <h4 class="text-lg font-medium text-gray-900">
          Current Transfer Costs ({transferCosts.length} entries)
        </h4>
        <Button onclick={downloadTransferCosts} variant="outline">
          <FileDown class="w-4 h-4 mr-2" />
          Download Current Costs
        </Button>
      </div>

      <div class="overflow-x-auto max-h-96">
        {#if transferCosts.length > 0}
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >Purchase Price</th
                >
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >Attorney Fee</th
                >
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >VAT</th
                >
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >Transfer Duty</th
                >
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >Deeds Office</th
                >
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >Total Cost</th
                >
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              {#each transferCosts as cost}
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                    >R{Number(cost.purchase_price).toLocaleString()}</td
                  >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                    >R{Number(cost.attorney_fee).toLocaleString()}</td
                  >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                    >R{Number(cost.vat).toLocaleString()}</td
                  >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                    >R{Number(cost.transfer_duty).toLocaleString()}</td
                  >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                    >R{Number(cost.deeds_office_fee).toLocaleString()}</td
                  >
                  <td
                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                    >R{Number(cost.total_cost).toLocaleString()}</td
                  >
                </tr>
              {/each}
            </tbody>
          </table>
        {:else}
          <div class="p-8 text-center text-gray-500">
            No transfer costs uploaded yet. Upload a CSV file to get started.
          </div>
        {/if}
      </div>
    </div>
  </div>
</div>
