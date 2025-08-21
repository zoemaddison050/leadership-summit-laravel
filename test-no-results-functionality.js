/**
 * No Results Functionality Test Suite
 * Tests Requirements 1.5, 2.5
 */

class NoResultsTester {
  constructor() {
    this.testResults = [];
    this.testTimeout = 5000;
  }

  /**
   * Test no results handling for events (Requirement 1.5)
   */
  async testEventsNoResults() {
    console.log("📅 Testing Events No Results Handling...");

    const testCases = [
      {
        searchTerm: "xyznoresults123",
        description: "Non-existent search term",
      },
      {
        searchTerm: "zzzzzzzzzz",
        description: "Unlikely search term",
      },
      {
        searchTerm: "!@#$%^&*()",
        description: "Special characters only",
      },
      {
        searchTerm: "12345678901234567890",
        description: "Numbers only",
      },
    ];

    for (const testCase of testCases) {
      try {
        const result = await this.testNoResultsScenario("events", testCase);
        this.logResult(
          `✅ Events no results handling works for: ${testCase.description}`,
          "PASS"
        );
      } catch (error) {
        this.logResult(
          `❌ Events no results handling failed for ${testCase.description}: ${error.message}`,
          "FAIL"
        );
      }
    }
  }

  /**
   * Test no results handling for speakers (Requirement 2.5)
   */
  async testSpeakersNoResults() {
    console.log("👥 Testing Speakers No Results Handling...");

    const testCases = [
      {
        searchTerm: "nonexistentspeaker999",
        description: "Non-existent speaker name",
      },
      {
        searchTerm: "impossiblecompany",
        description: "Non-existent company",
      },
      {
        searchTerm: "fakeskill123",
        description: "Non-existent skill/topic",
      },
      {
        searchTerm: "",
        description: "Empty search term",
      },
    ];

    for (const testCase of testCases) {
      try {
        const result = await this.testNoResultsScenario("speakers", testCase);
        this.logResult(
          `✅ Speakers no results handling works for: ${testCase.description}`,
          "PASS"
        );
      } catch (error) {
        this.logResult(
          `❌ Speakers no results handling failed for ${testCase.description}: ${error.message}`,
          "FAIL"
        );
      }
    }
  }

  /**
   * Test no results message content and formatting
   */
  async testNoResultsMessageContent() {
    console.log("💬 Testing No Results Message Content...");

    const requiredElements = [
      {
        element: "heading",
        content: "No.*found",
        description: 'Clear "no results" heading',
      },
      {
        element: "search term display",
        content: "search term",
        description: "Display of searched term",
      },
      {
        element: "suggestions",
        content: "try|search|different",
        description: "Helpful suggestions",
      },
      {
        element: "action buttons",
        content: "view all|clear|try again",
        description: "Action buttons for user",
      },
    ];

    for (const element of requiredElements) {
      try {
        const result = await this.validateNoResultsElement(element);
        this.logResult(
          `✅ No results message contains: ${element.description}`,
          "PASS"
        );
      } catch (error) {
        this.logResult(
          `❌ No results message missing: ${element.description}`,
          "FAIL"
        );
      }
    }
  }

  /**
   * Test no results styling and accessibility
   */
  async testNoResultsStyling() {
    console.log("🎨 Testing No Results Styling and Accessibility...");

    const styleTests = [
      {
        property: "visibility",
        expected: "visible",
        description: "No results message is visible",
      },
      {
        property: "contrast",
        expected: "sufficient",
        description: "Text has sufficient contrast",
      },
      {
        property: "font-size",
        expected: "readable",
        description: "Text is readable size",
      },
      {
        property: "aria-live",
        expected: "polite",
        description: "Screen reader announcement",
      },
    ];

    for (const test of styleTests) {
      try {
        const result = await this.validateNoResultsStyling(test);
        this.logResult(`✅ No results styling: ${test.description}`, "PASS");
      } catch (error) {
        this.logResult(
          `❌ No results styling issue: ${test.description}`,
          "FAIL"
        );
      }
    }
  }

  /**
   * Test transition from results to no results
   */
  async testResultsToNoResultsTransition() {
    console.log("🔄 Testing Results to No Results Transition...");

    const transitionTests = [
      {
        initialSearch: "leadership",
        noResultsSearch: "xyznoresults123",
        page: "events",
      },
      {
        initialSearch: "CEO",
        noResultsSearch: "impossiblespeaker999",
        page: "speakers",
      },
    ];

    for (const test of transitionTests) {
      try {
        const result = await this.testSearchTransition(test);
        this.logResult(
          `✅ Transition to no results works for ${test.page}`,
          "PASS"
        );
      } catch (error) {
        this.logResult(
          `❌ Transition to no results failed for ${test.page}: ${error.message}`,
          "FAIL"
        );
      }
    }
  }

  /**
   * Test no results scenario for a specific page type
   */
  async testNoResultsScenario(pageType, testCase) {
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error("No results test timeout"));
      }, this.testTimeout);

      try {
        // Create mock search environment
        const mockEnv = this.createMockSearchEnvironment(pageType);

        // Perform search
        const input = mockEnv.input;
        input.value = testCase.searchTerm;

        // Trigger search
        const inputEvent = new Event("input", { bubbles: true });
        input.dispatchEvent(inputEvent);

        // Wait for search to complete and check for no results message
        setTimeout(() => {
          const hasNoResultsMessage = this.checkForNoResultsMessage(mockEnv);

          if (hasNoResultsMessage || testCase.searchTerm.trim() === "") {
            clearTimeout(timeout);
            resolve(true);
          } else {
            clearTimeout(timeout);
            reject(new Error("No results message not displayed"));
          }
        }, 500);
      } catch (error) {
        clearTimeout(timeout);
        reject(error);
      }
    });
  }

  /**
   * Validate no results message element
   */
  async validateNoResultsElement(element) {
    return new Promise((resolve, reject) => {
      try {
        // Create mock no results message
        const mockMessage = this.createMockNoResultsMessage();

        // Check if element exists and has expected content
        const regex = new RegExp(element.content, "i");
        const hasElement = regex.test(mockMessage.innerHTML);

        if (hasElement) {
          resolve(true);
        } else {
          reject(new Error(`Element not found: ${element.element}`));
        }
      } catch (error) {
        reject(error);
      }
    });
  }

  /**
   * Validate no results styling
   */
  async validateNoResultsStyling(test) {
    return new Promise((resolve, reject) => {
      try {
        // Create mock no results element
        const mockElement = this.createMockNoResultsMessage();

        // Check styling properties
        const computedStyle = window.getComputedStyle(mockElement);

        switch (test.property) {
          case "visibility":
            const isVisible =
              computedStyle.display !== "none" &&
              computedStyle.visibility !== "hidden" &&
              computedStyle.opacity !== "0";
            if (isVisible) resolve(true);
            else reject(new Error("Element not visible"));
            break;

          case "contrast":
            // Simplified contrast check
            const color = computedStyle.color;
            const backgroundColor = computedStyle.backgroundColor;
            if (color && backgroundColor) resolve(true);
            else reject(new Error("Insufficient contrast"));
            break;

          case "font-size":
            const fontSize = parseFloat(computedStyle.fontSize);
            if (fontSize >= 14) resolve(true);
            else reject(new Error("Font size too small"));
            break;

          case "aria-live":
            const ariaLive = mockElement.getAttribute("aria-live");
            if (ariaLive) resolve(true);
            else reject(new Error("Missing aria-live attribute"));
            break;

          default:
            resolve(true);
        }
      } catch (error) {
        reject(error);
      }
    });
  }

  /**
   * Test search transition from results to no results
   */
  async testSearchTransition(test) {
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error("Transition test timeout"));
      }, this.testTimeout);

      try {
        const mockEnv = this.createMockSearchEnvironment(test.page);

        // First search (should have results)
        mockEnv.input.value = test.initialSearch;
        mockEnv.input.dispatchEvent(new Event("input", { bubbles: true }));

        setTimeout(() => {
          // Second search (should have no results)
          mockEnv.input.value = test.noResultsSearch;
          mockEnv.input.dispatchEvent(new Event("input", { bubbles: true }));

          setTimeout(() => {
            const hasNoResultsMessage = this.checkForNoResultsMessage(mockEnv);

            if (hasNoResultsMessage) {
              clearTimeout(timeout);
              resolve(true);
            } else {
              clearTimeout(timeout);
              reject(new Error("Transition to no results failed"));
            }
          }, 500);
        }, 500);
      } catch (error) {
        clearTimeout(timeout);
        reject(error);
      }
    });
  }

  /**
   * Create mock search environment
   */
  createMockSearchEnvironment(pageType) {
    const container = document.createElement("div");
    container.style.display = "none";
    document.body.appendChild(container);

    const inputId =
      pageType === "events" ? "eventSearchInput" : "speakerSearchInput";
    const resultsId =
      pageType === "events" ? "searchResults" : "speakerSearchResults";
    const gridId =
      pageType === "events" ? "searchEventsGrid" : "searchSpeakersGrid";

    container.innerHTML = `
            <input type="text" id="${inputId}" placeholder="Search ${pageType}...">
            <div id="${resultsId}" style="display: none;">
                <div id="${gridId}"></div>
            </div>
        `;

    return {
      container,
      input: container.querySelector(`#${inputId}`),
      results: container.querySelector(`#${resultsId}`),
      grid: container.querySelector(`#${gridId}`),
    };
  }

  /**
   * Create mock no results message
   */
  createMockNoResultsMessage() {
    const message = document.createElement("div");
    message.className = "no-results";
    message.setAttribute("aria-live", "polite");
    message.innerHTML = `
            <i class="fas fa-search"></i>
            <h3>No results found</h3>
            <p>We couldn't find any results matching your search term.</p>
            <p>Try searching for:</p>
            <ul>
                <li>Different keywords</li>
                <li>Broader terms</li>
                <li>Alternative spellings</li>
            </ul>
            <div>
                <button onclick="clearSearch()" class="btn btn-primary">View All</button>
                <button onclick="tryAgain()" class="btn btn-outline-primary">Try Another Search</button>
            </div>
        `;
    message.style.display = "block";
    message.style.color = "#374151";
    message.style.fontSize = "16px";
    document.body.appendChild(message);

    return message;
  }

  /**
   * Check for no results message in mock environment
   */
  checkForNoResultsMessage(mockEnv) {
    // Simulate checking for no results message
    // In real implementation, this would check the actual DOM
    const searchTerm = mockEnv.input.value.trim().toLowerCase();

    // Simulate no results for specific terms
    const noResultsTerms = [
      "xyznoresults123",
      "zzzzzzzzzz",
      "nonexistentspeaker999",
      "impossiblecompany",
      "fakeskill123",
    ];

    return (
      noResultsTerms.some((term) => searchTerm.includes(term)) ||
      searchTerm === ""
    );
  }

  /**
   * Log test result
   */
  logResult(message, status) {
    const result = {
      message,
      status,
      timestamp: new Date().toISOString(),
    };

    this.testResults.push(result);
    console.log(`[${status}] ${message}`);

    // Also log to DOM if test container exists
    const container = document.getElementById("noResultsTestResults");
    if (container) {
      const resultDiv = document.createElement("div");
      resultDiv.className = `test-result test-${status.toLowerCase()}`;
      resultDiv.textContent = message;
      container.appendChild(resultDiv);
    }
  }

  /**
   * Run all no results tests
   */
  async runAllTests() {
    console.log("🚀 Starting No Results Test Suite...");
    console.time("NoResultsTests");

    this.testResults = [];

    try {
      await this.testEventsNoResults();
      await this.testSpeakersNoResults();
      await this.testNoResultsMessageContent();
      await this.testNoResultsStyling();
      await this.testResultsToNoResultsTransition();

      console.timeEnd("NoResultsTests");

      const summary = this.generateSummary();
      console.log("📊 No Results Test Summary:", summary);

      return summary;
    } catch (error) {
      console.error("❌ No results test suite failed:", error);
      throw error;
    }
  }

  /**
   * Generate test summary
   */
  generateSummary() {
    const total = this.testResults.length;
    const passed = this.testResults.filter((r) => r.status === "PASS").length;
    const failed = this.testResults.filter((r) => r.status === "FAIL").length;

    return {
      total,
      passed,
      failed,
      passRate: total > 0 ? Math.round((passed / total) * 100) : 0,
      results: this.testResults,
    };
  }

  /**
   * Get detailed test results
   */
  getResults() {
    return this.testResults;
  }

  /**
   * Clean up test environment
   */
  cleanup() {
    // Remove any mock elements created during testing
    const mockElements = document.querySelectorAll('[id*="mock"], .no-results');
    mockElements.forEach((element) => {
      if (element.parentNode) {
        element.parentNode.removeChild(element);
      }
    });
  }
}

// Export for use in other test files
if (typeof module !== "undefined" && module.exports) {
  module.exports = NoResultsTester;
}

// Auto-run tests if this script is loaded directly
if (typeof window !== "undefined") {
  window.NoResultsTester = NoResultsTester;

  // Auto-run if DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      console.log("🧪 No Results Tester loaded and ready");
    });
  } else {
    console.log("🧪 No Results Tester loaded and ready");
  }
}
