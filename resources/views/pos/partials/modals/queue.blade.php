<!-- Offline queue viewer -->
<div class="modal fade" id="posOfflineQueueModal" tabindex="-1" aria-labelledby="posOfflineQueueModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-xl shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <div>
                    <h5 class="modal-title font-weight-bold" id="posOfflineQueueModalLabel">
                        <i class="fe fe-cloud-off text-warning mr-2"></i> Offline queue
                    </h5>
                    <p class="text-muted tx-13 mb-0">Pending until the server confirms — not finalized accounting offline.</p>
                </div>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge badge-light border px-3 py-2">
                        Total <strong class="ml-1" x-text="$store.pos.pendingSyncCount"></strong>
                    </span>
                    <span class="badge badge-success-transparent px-3 py-2">
                        Ready <strong class="ml-1" x-text="$store.pos.queueStatsPending"></strong>
                    </span>
                    <span class="badge badge-danger-transparent px-3 py-2" x-show="$store.pos.queueStatsFailed > 0">
                        Failed retry <strong class="ml-1" x-text="$store.pos.queueStatsFailed"></strong>
                    </span>
                </div>
                <div class="table-responsive rounded border" style="max-height: 320px;">
                    <table class="table table-sm table-hover mb-0 tx-13">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th>Queued</th>
                                <th>Attempts</th>
                                <th>Status</th>
                                <th>Error</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in $store.pos.offlineQueueRows" :key="row.id">
                                <tr>
                                    <td class="text-monospace" x-text="new Date(row.createdAt).toLocaleString()"></td>
                                    <td x-text="row.attempts || 0"></td>
                                    <td>
                                        <span class="badge badge-warning-transparent" x-show="(row.attempts || 0) > 0 && row.lastError">Retry</span>
                                        <span class="badge badge-success-transparent" x-show="!(row.attempts || 0)">Waiting</span>
                                    </td>
                                    <td class="text-break" style="max-width:180px;font-size:11px;" x-text="row.lastError || '—'"></td>
                                    <td class="text-right">
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded" @click="$store.pos.removeOfflineQueueRow(row.id)">Remove</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted tx-12 mt-2 mb-0" x-show="$store.pos.offlineQueueRows.length === 0">No queued sales.</p>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-primary rounded-pill px-4" @click="$store.pos.retryAllQueuedSales()">
                    <i class="fe fe-refresh-cw mr-1"></i> Sync now
                </button>
                <button type="button" class="btn btn-light rounded-pill" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
