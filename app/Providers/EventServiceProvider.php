<?php

namespace App\Providers;

use App\Events\Domain\Inventory\GoodsReceived;
use App\Events\Domain\Inventory\SupplierPaymentRecorded;
use App\Events\Domain\Sales\CustomerPaymentRecorded;
use App\Events\Domain\Sales\RefundRecorded;
use App\Events\Domain\Sales\SaleRecorded;
use App\Events\Domain\Sales\WebOrderRecorded;
use App\Listeners\Accounting\PostGoodsReceivedJournalListener;
use App\Listeners\Accounting\PostSupplierPaymentJournalListener;
use App\Listeners\Accounting\PostCustomerPaymentJournalListener;
use App\Listeners\Accounting\PostRefundJournalListener;
use App\Listeners\Accounting\PostSaleJournalListener;
use App\Listeners\Accounting\PostWebOrderJournalListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        SaleRecorded::class => [
            PostSaleJournalListener::class,
        ],
        GoodsReceived::class => [
            PostGoodsReceivedJournalListener::class,
        ],
        WebOrderRecorded::class => [
            PostWebOrderJournalListener::class,
        ],
        RefundRecorded::class => [
            PostRefundJournalListener::class,
        ],
        SupplierPaymentRecorded::class => [
            PostSupplierPaymentJournalListener::class,
        ],
        CustomerPaymentRecorded::class => [
            PostCustomerPaymentJournalListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
