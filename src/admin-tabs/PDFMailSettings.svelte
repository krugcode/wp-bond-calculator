<script lang="ts">
  import { Button } from "$lib/ui/button";
  import { Download, Save } from "@lucide/svelte";

  let { pdfSettings } = $props();

  // PDF Settings
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

  async function saveSettings() {
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

  function handleSaveSettings() {
    saveSettings();
  }

  interface WindowWithBCAdmin extends Window {
    bcAdmin: {
      apiUrl: string;
      nonce: string;
    };
  }
</script>

<div class="max-w-4xl space-y-6">
  <!-- PDF Generation Settings -->
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
      <!-- API2PDF API Key -->
      <div>
        <label
          for="api2pdf-key"
          class="block text-sm font-medium text-gray-700 mb-2"
        >
          API2PDF API Key
        </label>
        <input
          id="api2pdf-key"
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

      <!-- Brevo API Key -->
      <div>
        <label
          for="brevo-key"
          class="block text-sm font-medium text-gray-700 mb-2"
        >
          Brevo API Key
        </label>
        <input
          id="brevo-key"
          type="password"
          bind:value={pdfSettings.brevo_api_key}
          placeholder="Enter your Brevo API key"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <p class="mt-1 text-xs text-gray-500">
          Get your API key from <a
            href="https://app.brevo.com/settings/keys/api"
            target="_blank"
            class="text-blue-600 hover:text-blue-800">Brevo API Keys page</a
          >
        </p>
      </div>

      <!-- Email Settings -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label
            for="sender-email"
            class="block text-sm font-medium text-gray-700 mb-2"
          >
            Sender Email
          </label>
          <input
            id="sender-email"
            type="email"
            bind:value={pdfSettings.sender_email}
            placeholder="noreply@yourdomain.com"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <div>
          <label
            for="sender-name"
            class="block text-sm font-medium text-gray-700 mb-2"
          >
            Sender Name
          </label>
          <input
            id="sender-name"
            type="text"
            bind:value={pdfSettings.sender_name}
            placeholder="Bond Calculator"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
      </div>

      <div>
        <label
          for="subject-line"
          class="block text-sm font-medium text-gray-700 mb-2"
        >
          Email Subject Template
        </label>
        <input
          id="subject-line"
          type="text"
          bind:value={pdfSettings.subject_line}
          placeholder="Your [CALCULATOR_TYPE] Calculator Results"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <p class="mt-1 text-xs text-gray-500">
          Use [CALCULATOR_TYPE] to dynamically insert "Transfer Cost" or "Bond
          Cost"
        </p>
      </div>

      <!-- Template Editor (disabled for now) -->
      <div class="opacity-50">
        <label
          for="template"
          class="block text-sm font-medium text-gray-700 mb-2"
        >
          PDF Template (HTML) - Coming Soon
        </label>
        <textarea
          id="template"
          bind:value={pdfSettings.template_html}
          rows="6"
          disabled
          placeholder="Custom HTML template functionality will be available in a future update"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm bg-gray-50"
        ></textarea>
        <p class="mt-1 text-xs text-gray-500">
          Currently using default template. Custom templates coming soon.
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex space-x-3">
        <Button onclick={handleSaveSettings} disabled={pdfSaving}>
          {#if pdfSaving}
            <div
              class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"
            ></div>
            Saving...
          {:else}
            <Save class="w-4 h-4 mr-2" />
            Save Settings
          {/if}
        </Button>

        <Button
          onclick={handleDownloadExample}
          disabled={pdfLoading || !pdfSettings?.api_key?.trim()}
          variant="secondary"
        >
          {#if pdfLoading}
            <div
              class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-600 mr-2"
            ></div>
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
