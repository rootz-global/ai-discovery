/**
 * Rootz AI Discovery — WebMCP Tools for WordPress
 *
 * Registers site tools with browser-based AI assistants via navigator.modelContext.
 * Requires Chrome 146+ with WebMCP flag enabled.
 *
 * @license GPL-2.0-or-later
 * @see https://rootz.global/ai-discovery
 */
(function () {
    'use strict';

    // Only register if WebMCP API is available.
    if (typeof navigator === 'undefined' || !navigator.modelContext) {
        return;
    }

    // Site config injected by WordPress via wp_localize_script.
    var config = window.rootzAiDiscovery || {};
    var siteUrl = config.siteUrl || window.location.origin;
    var apiUrl = config.apiUrl || siteUrl + '/wp-json/rootz/v1/';
    var wellKnown = config.wellKnown || siteUrl + '/.well-known/ai';
    var siteName = config.siteName || document.title;

    /**
     * Tool: getOrganizationInfo
     * Fetches the site's AI Discovery endpoint.
     */
    navigator.modelContext.registerTool({
        name: 'getOrganizationInfo',
        description: 'Get structured identity and capabilities for ' + siteName + '. Returns organization name, domain, sector, policies, and available AI tools.',
        inputSchema: {
            type: 'object',
            properties: {},
            required: []
        },
        annotations: {
            readOnlyHint: true
        },
        execute: async function () {
            var response = await fetch(wellKnown);
            if (!response.ok) {
                return { error: 'Failed to fetch AI Discovery endpoint', status: response.status };
            }
            var data = await response.json();
            data._source = 'webmcp';
            data._proofLevel = 'content-integrity';
            data._whatThisProves = 'This data was served by the domain ' + window.location.hostname + ' over HTTPS. DNS confirms domain ownership.';
            data._whatThisDoesNotProve = 'The organization identity is self-declared. No third-party verification.';
            return data;
        }
    });

    /**
     * Tool: getPolicies
     * Fetches machine-readable content policies.
     */
    navigator.modelContext.registerTool({
        name: 'getPolicies',
        description: 'Get machine-readable content policies for ' + siteName + '. Returns content licensing, AI training permissions, quoting rules, and privacy information.',
        inputSchema: {
            type: 'object',
            properties: {},
            required: []
        },
        annotations: {
            readOnlyHint: true
        },
        execute: async function () {
            var response = await fetch(apiUrl + 'policies');
            if (!response.ok) {
                return { error: 'Failed to fetch policies', status: response.status };
            }
            var data = await response.json();
            data._proofLevel = 'content-integrity';
            data._whatThisProves = 'The site owner configured these policy declarations. They represent the site intent for AI content usage.';
            data._whatThisDoesNotProve = 'These are preferences, not legal enforcement. Compliance depends on agent behavior.';
            return data;
        }
    });

    /**
     * Tool: getKnowledge
     * Fetches the auto-generated knowledge base.
     */
    navigator.modelContext.registerTool({
        name: 'getKnowledge',
        description: 'Get structured knowledge base for ' + siteName + '. Returns organization info, products, about page summary, and glossary of key terms.',
        inputSchema: {
            type: 'object',
            properties: {},
            required: []
        },
        annotations: {
            readOnlyHint: true
        },
        execute: async function () {
            var response = await fetch(apiUrl + 'knowledge');
            if (!response.ok) {
                return { error: 'Failed to fetch knowledge base', status: response.status };
            }
            return await response.json();
        }
    });

    /**
     * Tool: getFeed
     * Fetches the AI-optimized content feed.
     */
    navigator.modelContext.registerTool({
        name: 'getFeed',
        description: 'Get AI-optimized feed of recent content from ' + siteName + '. Returns structured posts with titles, summaries, categories, tags, and content licenses.',
        inputSchema: {
            type: 'object',
            properties: {
                limit: {
                    type: 'number',
                    description: 'Maximum number of items to return (default: 20)'
                }
            },
            required: []
        },
        annotations: {
            readOnlyHint: true
        },
        execute: async function (params) {
            var response = await fetch(apiUrl + 'feed');
            if (!response.ok) {
                return { error: 'Failed to fetch feed', status: response.status };
            }
            var data = await response.json();
            if (params && params.limit && data.items) {
                data.items = data.items.slice(0, params.limit);
            }
            return data;
        }
    });
})();
