<div class="workflow-actions">
    @if ($serviceRequest->status === \App\Models\ServiceRequest::STATUS_PENDING)
        <form action="{{ route('service-requests.start', $serviceRequest->id) }}" method="POST">
            @csrf
            <button class="action-link" type="submit">Start Processing</button>
        </form>
        <form action="{{ route('service-requests.cancel', $serviceRequest->id) }}" method="POST">
            @csrf
            <button class="action-link action-link--quiet" type="submit">Cancel</button>
        </form>
    @elseif ($serviceRequest->status === \App\Models\ServiceRequest::STATUS_IN_PROGRESS)
        <form action="{{ route('service-requests.complete', $serviceRequest->id) }}" method="POST">
            @csrf
            <button class="action-link" type="submit">Complete</button>
        </form>
        <form action="{{ route('service-requests.cancel', $serviceRequest->id) }}" method="POST">
            @csrf
            <button class="action-link action-link--quiet" type="submit">Cancel</button>
        </form>
    @else
        <span class="terminal-note">No further actions</span>
    @endif
</div>
