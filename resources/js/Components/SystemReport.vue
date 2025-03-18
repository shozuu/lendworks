<script setup>
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Eye, Download, FileText } from "lucide-vue-next"; // Removed FileIcon import
import { formatDate } from "@/lib/formatters";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { ref } from "vue";

const props = defineProps({
    stats: {
        type: Object,
        required: true
    }
});

const showPreview = ref(false);
const reportContent = ref('');

const generateReportContent = () => {
    return `LENDWORKS SYSTEM PERFORMANCE REPORT
Generated on: ${formatDate(new Date())}

EXECUTIVE SUMMARY
----------------
Total Users: ${props.stats.totalUsers}
Total Listings: ${props.stats.totalListings}
Platform Revenue: ₱${props.stats.revenueStats.total.toLocaleString()}

PERFORMANCE METRICS
-----------------
Success Rate: ${props.stats.performanceStats.successRate}%
Dispute Rate: ${props.stats.performanceStats.disputeRate}%

TRANSACTION ANALYSIS
------------------
Completed Transactions (30 days): ${props.stats.transactionStats.completed}
Active Rentals: ${props.stats.transactionStats.active}
Monthly Revenue: ₱${props.stats.revenueStats.monthly.toLocaleString()}

USER STATISTICS
-------------
Total Registered Users: ${props.stats.totalUsers}
Verified Users: ${props.stats.verifiedUsers}
Active Users: ${props.stats.activeUsers}
Suspended Users: ${props.stats.suspendedUsers}
New Users This Month: ${props.stats.newUsersThisMonth}

LISTING ANALYSIS
--------------
Total Listings: ${props.stats.totalListings}
Active Listings: ${props.stats.activeListings}
Average Price: ₱${Math.round(props.stats.averageListingPrice).toLocaleString()}
Price Range: ₱${Math.round(props.stats.lowestListingPrice).toLocaleString()} - ₱${Math.round(props.stats.highestListingPrice).toLocaleString()}

Listing Status Distribution:
- Approved: ${props.stats.listingStats.approved}
- Rejected: ${props.stats.listingStats.rejected}
- Taken Down: ${props.stats.listingStats.takenDown}

CATEGORY PERFORMANCE
------------------
${props.stats.categoryBreakdown.map(cat => 
`${cat.name}:
- Count: ${cat.count}
- Average Price: ₱${Math.round(cat.average_price).toLocaleString()}
- Market Share: ${Math.round((cat.count / props.stats.totalListings) * 100)}%`
).join('\n\n')}

TODAY'S ACTIVITY
--------------
New Users: ${props.stats.recentActivity.newUsersToday}
New Listings: ${props.stats.recentActivity.newListingsToday}
Pending Approvals: ${props.stats.recentActivity.pendingApprovalsToday}

SYSTEM EVALUATION
---------------
1. User Growth: ${evaluateUserGrowth(props.stats)}
2. Platform Activity: ${evaluatePlatformActivity(props.stats)}
3. Revenue Performance: ${evaluateRevenue(props.stats)}
4. System Health: ${evaluateSystemHealth(props.stats)}

RECOMMENDATIONS
-------------
${generateRecommendations(props.stats)}
`;
};

const generateReport = (format = 'txt') => {
    reportContent.value = generateReportContent();

    if (format === 'preview') {
        showPreview.value = true;
    } else {
        downloadTxt();
    }
};

const downloadTxt = () => {
    const blob = new Blob([reportContent.value], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `system-report-${formatDate(new Date())}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
};

// Helper functions for evaluation
const evaluateUserGrowth = (stats) => {
    const monthlyGrowthRate = (stats.newUsersThisMonth / stats.totalUsers) * 100;
    const verificationRate = (stats.verifiedUsers / stats.totalUsers) * 100;
    
    let evaluation = [];
    
    // Growth rate evaluation
    if (monthlyGrowthRate > 10) {
        evaluation.push(`Excellent user growth rate at ${monthlyGrowthRate.toFixed(1)}%`);
    } else if (monthlyGrowthRate > 5) {
        evaluation.push(`Good user growth rate at ${monthlyGrowthRate.toFixed(1)}%`);
    } else {
        evaluation.push(`Moderate user growth rate at ${monthlyGrowthRate.toFixed(1)}%`);
    }
    
    // Verification rate evaluation
    evaluation.push(`User verification rate is at ${verificationRate.toFixed(1)}%`);
    
    return evaluation.join('. ');
};

const evaluatePlatformActivity = (stats) => {
    const activeListingRate = (stats.activeListings / stats.totalListings) * 100;
    const pendingRate = (stats.pendingApprovals / stats.totalListings) * 100;
    
    let evaluation = [];
    
    // Active listings evaluation
    evaluation.push(
        `Platform shows ${activeListingRate.toFixed(1)}% listing activity rate with ` +
        `${stats.transactionStats.active} active rentals`
    );
    
    // Pending approvals evaluation
    if (pendingRate > 10) {
        evaluation.push(`High pending approval rate at ${pendingRate.toFixed(1)}%`);
    }
    
    // Category distribution insight
    const topCategory = [...stats.categoryBreakdown]
        .sort((a, b) => b.count - a.count)[0];
    evaluation.push(
        `Most popular category is ${topCategory.name} with ` +
        `${Math.round((topCategory.count / stats.totalListings) * 100)}% market share`
    );
    
    return evaluation.join('. ');
};

const evaluateRevenue = (stats) => {
    const monthlyRevenue = stats.revenueStats.monthly;
    const totalRevenue = stats.revenueStats.total;
    const monthlyRate = (monthlyRevenue / totalRevenue) * 100;
    const averageTransaction = stats.transactionStats.completed > 0 
        ? totalRevenue / stats.transactionStats.completed 
        : 0;
    
    let evaluation = [];
    
    // Monthly performance
    evaluation.push(
        `Current month represents ${monthlyRate.toFixed(1)}% of total revenue ` +
        `(₱${monthlyRevenue.toLocaleString()})`
    );
    
    // Average transaction value
    evaluation.push(
        `Average transaction value is ₱${Math.round(averageTransaction).toLocaleString()}`
    );
    
    // Trend analysis
    if (monthlyRate > 20) {
        evaluation.push('Showing strong upward revenue trend');
    } else if (monthlyRate > 10) {
        evaluation.push('Showing stable revenue pattern');
    } else {
        evaluation.push('Revenue growth opportunity identified');
    }
    
    return evaluation.join('. ');
};

const evaluateSystemHealth = (stats) => {
    const successRate = stats.performanceStats.successRate;
    const disputeRate = stats.performanceStats.disputeRate;
    return `System maintains ${successRate}% success rate with ${disputeRate}% dispute rate. ` +
           `${successRate > 90 ? 'Excellent' : successRate > 80 ? 'Good' : 'Fair'} overall system health.`;
};

const generateRecommendations = (stats) => {
    const recommendations = [];
    const monthlyGrowthRate = (stats.newUsersThisMonth / stats.totalUsers) * 100;
    const verificationRate = (stats.verifiedUsers / stats.totalUsers) * 100;
    const activeListingRate = (stats.activeListings / stats.totalListings) * 100;
    
    // User-related recommendations
    if (verificationRate < 80) {
        recommendations.push(
            `- Implement email verification reminders (current rate: ${verificationRate.toFixed(1)}%)`
        );
    }
    
    // Listing-related recommendations
    if (stats.pendingApprovals > 10) {
        recommendations.push(
            `- Expedite ${stats.pendingApprovals} pending listing approvals to maintain momentum`
        );
    }
    
    if (activeListingRate < 60) {
        recommendations.push(
            `- Encourage more active listings (current rate: ${activeListingRate.toFixed(1)}%)`
        );
    }
    
    // Transaction-related recommendations
    if (stats.performanceStats.disputeRate > 5) {
        recommendations.push(
            `- Review dispute resolution process (current rate: ${stats.performanceStats.disputeRate}%)`
        );
    }
    
    // Growth-related recommendations
    if (monthlyGrowthRate < 5) {
        recommendations.push(
            `- Consider user referral program to boost growth (current rate: ${monthlyGrowthRate.toFixed(1)}%)`
        );
    }
    
    // Category-related recommendations
    const lowPerformingCategories = stats.categoryBreakdown
        .filter(cat => (cat.count / stats.totalListings) * 100 < 5)
        .map(cat => cat.name);
    
    if (lowPerformingCategories.length > 0) {
        recommendations.push(
            `- Promote underutilized categories: ${lowPerformingCategories.join(', ')}`
        );
    }

    return recommendations.length 
        ? recommendations.join('\n')
        : '- System is performing optimally, maintain current strategies';
};
</script>

<template>
    <Card class="mb-6">
        <CardHeader>
            <CardTitle>System Report</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-sm text-muted-foreground">
                        Generate a comprehensive system performance report with analysis and recommendations
                    </p>
                </div>
                
                <div class="flex gap-2">
                    <Button @click="generateReport('preview')" variant="outline" class="gap-2">
                        <Eye class="h-4 w-4" />
                        Preview
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>

    <!-- Report Preview Dialog -->
    <Dialog :open="showPreview" @update:open="showPreview = $event">
        <DialogContent class="max-w-4xl">
            <DialogHeader>
                <DialogTitle>System Report Preview</DialogTitle>
            </DialogHeader>
            <!-- Updated styling for better scrolling -->
            <div class="py-4">
                <div class="max-h-[60vh] overflow-y-auto rounded-lg">
                    <pre class="whitespace-pre-wrap text-sm p-4 bg-muted/50">{{ reportContent }}</pre>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t">
                <Button variant="outline" @click="showPreview = false">
                    Close
                </Button>
                <Button @click="downloadTxt()" class="gap-2">
                    <FileText class="h-4 w-4" />
                    Download
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
/* Add custom scrollbar styling */
.max-h-[60vh] {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.max-h-[60vh]::-webkit-scrollbar {
    width: 8px;
}

.max-h-[60vh]::-webkit-scrollbar-track {
    background: transparent;
}

.max-h-[60vh]::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
</style>
