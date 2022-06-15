<?php

declare(strict_types=1);

namespace App\Services\Application\Commands\DNS;

use Exception;
use Illuminate\Support\Facades\DB;

final class FetchService
{
    private $dnsRecodeTypeQueryService;

    private $subdomainRepository;

    private $makeDnsRecordService;

    private $dnsTypes;

    public function __construct(
        \App\Infrastructures\Queries\Dns\EloquentDnsRecordTypeQueryServiceInterface $dnsRecodeTypeQueryService,
        \App\Infrastructures\Repositories\Subdomain\SubdomainRepositoryInterface $subdomainRepository,
        \App\Services\Domain\Subdomain\DNS\MakeDnsRecordService $makeDnsRecordService
    ) {
        $this->dnsRecodeTypeQueryService = $dnsRecodeTypeQueryService;
        $this->subdomainRepository = $subdomainRepository;
        $this->makeDnsRecordService = $makeDnsRecordService;
    }

    /**
     * @return void
     */
    private function initDnsRecodeTypeNames(): void
    {
        $dnsRecordTypes = $this->dnsRecodeTypeQueryService->getSortAll();

        $this->dnsTypes = $dnsRecordTypes->pluck('name', 'id')->toArray();
    }

    /**
     * @param \App\Infrastructures\Models\DnsRecord $dnsRecord
     * @param \App\Infrastructures\Models\Eloquent\Subdomain $subdomain
     * @return void
     */
    private function executeOfDnsRecordBySubdomain(
        \App\Infrastructures\Models\DnsRecord $dnsRecord,
        \App\Infrastructures\Models\Eloquent\Subdomain $subdomain
    ): void {
        if (in_array($dnsRecord->getType(), $this->dnsTypes)) {
            $this->subdomainRepository->updateOfTtlPriority(
                $subdomain->domain_id,
                $dnsRecord->getTypeId(),
                $subdomain->prefix,
                $dnsRecord->getValue(),
                $dnsRecord->getTtl(),
                $dnsRecord->getPriority(),
            );
        }
    }

    /**
     * @param \App\Infrastructures\Models\Eloquent\Subdomain $subdomain
     * @return void
     */
    private function executeOfSubdomain(
        \App\Infrastructures\Models\Eloquent\Subdomain $subdomain
    ): void {
        $subdomain->delete();

        $dnsRecords = $this->makeDnsRecordService->make($subdomain);

        foreach ($dnsRecords as $dnsRecord) {
            $this->executeOfDnsRecordBySubdomain($dnsRecord, $subdomain);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $subdomains
     * @return void
     */
    public function handle(
        \Illuminate\Database\Eloquent\Collection $subdomains
    ): void {
        $this->initDnsRecodeTypeNames();

        $subdomainCollapse = $subdomains->collapse();

        DB::beginTransaction();
        try {
            foreach ($subdomainCollapse as $subdomain) {
                $this->executeOfSubdomain($subdomain);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }
}