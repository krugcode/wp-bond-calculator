<script lang="ts">
  import { Button } from "$lib/ui/button";
  import { Download } from "@lucide/svelte";

  async function savePdfSettings() {}

  let { pdfSettings } = $props();
  // PDF Settings (existing)
  let pdfLoading = $state(false);
  let pdfSaving = $state(false);
  let pdfMessage = $state("");

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

  function handleDownloadExample() {
    downloadExample();
  }
</script>

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
            class="text-blue-600 hover:text-blue-800">portal.api2pdf.com</a
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
          onclick={handleDownloadExample}
          disabled={pdfLoading || !pdfSettings?.api_key?.trim()}
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
