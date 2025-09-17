<script lang="ts">
  import { Button } from "$lib/ui/button";
  import { AlertCircle, CheckCircle, FileDown, Upload } from "@lucide/svelte";

  let { bondCosts } = $props();
  let bondUploadMessage = $state("");

  let bondUploading = $state(false);

  // File inputs
  let bondFileInput;

  async function downloadBondCosts() {
    try {
      const response = await fetch(
        `${window.bcAdmin.apiUrl}/download-bond-costs`,
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
        a.download = "bond-costs.csv";
        a.click();
        window.URL.revokeObjectURL(url);
      }
    } catch (err) {
      bondUploadMessage = `Download Error: ${err.message}`;
    }
  }
  async function handleBondUpload() {
    const file = bondFileInput.files[0];
    if (!file) {
      bondUploadMessage = "Please select a CSV file";
      return;
    }

    if (!file.name.toLowerCase().endsWith(".csv")) {
      bondUploadMessage = "Please select a valid CSV file";
      return;
    }

    bondUploading = true;
    bondUploadMessage = "";

    try {
      const formData = new FormData();
      formData.append("csv_file", file);

      const response = await fetch(
        `${window.bcAdmin.apiUrl}/upload-bond-costs`,
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
        bondUploadMessage = `Success: Uploaded ${result.count} bond cost entries`;
        bondCosts = result.data;
        bondFileInput.value = ""; // Clear file input
      } else {
        bondUploadMessage = `Error: ${result.message}`;
      }
    } catch (err) {
      bondUploadMessage = `Error: ${err.message}`;
    } finally {
      bondUploading = false;
    }
  }
</script>

<div class="max-w-6xl space-y-6">
  <div>
    <h3 class="text-lg font-medium text-gray-900 mb-4">Bond Cost Management</h3>

    <!-- Upload/Download Controls -->
    <div class="bg-gray-50 p-6 rounded-lg mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Upload Bond Costs CSV
          </label>
          <div class="flex items-center space-x-2">
            <input
              bind:this={bondFileInput}
              type="file"
              accept=".csv"
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            <Button
              onclick={handleBondUpload}
              disabled={bondUploading}
              size="sm"
            >
              {#if bondUploading}
                <div
                  class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"
                ></div>
              {:else}
                <Upload class="w-4 h-4 mr-2" />
              {/if}
              Upload
            </Button>
          </div>

          {#if bondUploadMessage}
            <div
              class="mt-2 p-3 rounded-md {bondUploadMessage.startsWith(
                'Success',
              )
                ? 'bg-green-50 text-green-800'
                : 'bg-red-50 text-red-800'}"
            >
              {#if bondUploadMessage.startsWith("Success")}
                <CheckCircle class="w-4 h-4 inline mr-2" />
              {:else}
                <AlertCircle class="w-4 h-4 inline mr-2" />
              {/if}
              {bondUploadMessage}
            </div>
          {/if}
        </div>
      </div>
    </div>

    <!-- Current Costs Table -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
      <div
        class="flex justify-between items-center px-6 py-4 border-b border-gray-200"
      >
        <h4 class="text-lg font-medium text-gray-900">
          Current Bond Costs ({bondCosts.length} entries)
        </h4>
        <Button onclick={downloadBondCosts} variant="outline">
          <FileDown class="w-4 h-4 mr-2" />
          Download Current Costs
        </Button>
      </div>

      <div class="overflow-x-auto max-h-96">
        {#if bondCosts.length > 0}
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >Bond Amount</th
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
                  >Deeds Office</th
                >
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >Total Cost</th
                >
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              {#each bondCosts as cost}
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                    >R{Number(cost.bond_amount).toLocaleString()}</td
                  >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                    >R{Number(cost.attorney_fee).toLocaleString()}</td
                  >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                    >R{Number(cost.vat).toLocaleString()}</td
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
            No bond costs uploaded yet. Upload a CSV file to get started.
          </div>
        {/if}
      </div>
    </div>
  </div>
</div>
