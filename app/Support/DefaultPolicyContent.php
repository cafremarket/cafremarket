<?php

namespace App\Support;

use App\Models\Page;

/**
 * Default legal / policy HTML used when CMS content is empty or still a seeder placeholder.
 * Kept in sync with Flutter DefaultLegalContent for App Store / Play review readiness.
 */
class DefaultPolicyContent
{
    public static function brandName(): string
    {
        return e(get_platform_title() ?: 'Cafre Market');
    }

    public static function website(): string
    {
        return e(rtrim((string) (config('app.url') ?: 'https://cafremarket.someserve.com'), '/'));
    }

    public static function supportEmail(): string
    {
        return e((string) (config('system_settings.support_email')
            ?: config('mail.from.address')
            ?: 'support@cafremarket.someserve.com'));
    }

    /**
     * Resolve display HTML for a slug: CMS content if usable, otherwise default fallback.
     */
    public static function resolve(string $slug, ?string $remoteContent = null): string
    {
        if (! PolicyPages::isPlaceholder($remoteContent)) {
            return trim((string) $remoteContent);
        }

        return self::forSlug($slug);
    }

    /**
     * Default HTML for a known policy slug (empty string if unknown).
     */
    public static function forSlug(string $slug): string
    {
        return match ($slug) {
            Page::PAGE_PRIVACY_POLICY => self::privacyPolicyHtml(),
            Page::PAGE_TNC_FOR_CUSTOMER => self::termsHtml('customers'),
            Page::PAGE_TNC_FOR_MERCHANT => self::termsHtml('merchants, vendors, and delivery partners'),
            Page::PAGE_RETURN_AND_REFUND => self::returnAndRefundHtml(),
            Page::PAGE_ABOUT_US => self::aboutUsHtml(),
            Page::PAGE_CONTACT_US => self::contactUsHtml(),
            default => '',
        };
    }

    public static function privacyPolicyHtml(): string
    {
        $brand = self::brandName();
        $website = self::website();
        $email = self::supportEmail();
        $updated = 'July 30, 2026';

        return <<<HTML
<h2>Privacy Policy</h2>
<p><strong>Last updated:</strong> {$updated}</p>
<p>This Privacy Policy describes how <strong>{$brand}</strong> (“we”, “us”, or “our”) collects, uses, and shares information when you use our website, mobile applications, and related marketplace services available at <a href="{$website}">{$website}</a>.</p>
<p>By using our services, you agree to this Privacy Policy. If you do not agree, please do not use the services.</p>

<h3>1. Information We Collect</h3>
<p>Depending on how you use the platform, we may collect:</p>
<ul>
  <li><strong>Account information</strong> — name, email address, phone number, password, and profile details you provide.</li>
  <li><strong>Business / delivery information</strong> — shop details, product listings, order information, addresses, and delivery status (as applicable to your role).</li>
  <li><strong>Transaction information</strong> — order history, payment status, and related receipts. Payment card details are processed by third-party payment providers; we do not store full card numbers in our apps.</li>
  <li><strong>Device &amp; technical data</strong> — device type, OS version, app version, IP address, language, and crash/diagnostic logs needed to operate the service.</li>
  <li><strong>Push notification tokens</strong> — used to send order, delivery, and account notifications if you grant permission.</li>
  <li><strong>Photos / camera (if you grant access)</strong> — used only for features you choose, such as profile photos, product images, chat attachments, dispute evidence, or QR scanning.</li>
  <li><strong>Advertising identifier (IDFA / AAID)</strong> — only if you grant App Tracking Transparency permission on iOS or equivalent permission on Android. If you decline, we do not use this identifier for tracking.</li>
</ul>

<h3>2. How We Use Information</h3>
<ul>
  <li>Create and manage your account</li>
  <li>Process orders, deliveries, and marketplace interactions</li>
  <li>Provide customer, seller, or delivery support</li>
  <li>Send transactional notifications and important service updates</li>
  <li>Improve performance, security, and reliability</li>
  <li>Comply with legal obligations</li>
  <li>Measure marketing performance <em>only</em> if you have consented to tracking where required by law or platform rules</li>
</ul>
<p>We do <strong>not</strong> sell your personal information to data brokers.</p>

<h3>3. Tracking &amp; Advertising</h3>
<p>On iOS, tracking requires your permission through App Tracking Transparency. On Android, advertising identifiers follow Google Play advertising ID / user-controls rules.</p>
<p>Account data such as name, email, and phone is collected for <strong>app functionality</strong> (login, orders, delivery, support). It is not used for cross-app advertising tracking unless you have separately consented and our store privacy disclosures state otherwise.</p>

<h3>4. Sharing of Information</h3>
<p>We may share information with:</p>
<ul>
  <li>Other marketplace participants as needed to complete an order (for example, seller, customer, or delivery partner details relevant to that order)</li>
  <li>Service providers that help us operate the platform (hosting, analytics limited to service operation, push notifications, payment processors)</li>
  <li>Authorities when required by law or to protect rights, safety, and security</li>
</ul>
<p>Third parties are expected to use information only to perform services for us and in accordance with applicable law.</p>

<h3>5. Data Retention</h3>
<p>We retain personal information only as long as needed to provide the service, meet legal/accounting requirements, resolve disputes, and enforce agreements. When you delete your account, we delete or anonymize personal data associated with that account except where retention is legally required.</p>

<h3>6. Account Deletion</h3>
<p>You can request deletion of your account from within the relevant app (Settings / Account → Delete Account), where available, or by contacting us. Deletion removes access to your account and deletes associated personal data subject to legal retention requirements.</p>

<h3>7. Your Choices &amp; Rights</h3>
<ul>
  <li>Update profile information in the app or website</li>
  <li>Control notification, camera, photo, and tracking permissions in device Settings</li>
  <li>Request access, correction, or deletion of personal data by contacting us</li>
</ul>
<p>Depending on your location, you may have additional rights under applicable privacy laws.</p>

<h3>8. Children’s Privacy</h3>
<p>Our services are not directed to children under 13 (or the minimum age required in your country). We do not knowingly collect personal information from children. If you believe a child has provided personal information, contact us and we will take appropriate steps to delete it.</p>

<h3>9. Security</h3>
<p>We use reasonable administrative, technical, and organizational measures to protect personal information. No method of transmission or storage is 100% secure.</p>

<h3>10. International Users</h3>
<p>If you access the service from outside our primary operating region, your information may be processed in countries where we or our providers operate.</p>

<h3>11. Changes</h3>
<p>We may update this Privacy Policy from time to time. The “Last updated” date will change when we do. Continued use of the services after updates means you accept the revised policy.</p>

<h3>12. Contact</h3>
<p>Questions about privacy or data requests:<br>
Email: <a href="mailto:{$email}">{$email}</a><br>
Website: <a href="{$website}">{$website}</a></p>
HTML;
    }

    /**
     * @param  string  $audience  Human-readable audience phrase
     */
    public static function termsHtml(string $audience = 'users'): string
    {
        $brand = self::brandName();
        $website = self::website();
        $email = self::supportEmail();
        $updated = 'July 30, 2026';
        $audience = e($audience);

        return <<<HTML
<h2>Terms and Conditions</h2>
<p><strong>Last updated:</strong> {$updated}</p>
<p>These Terms and Conditions (“Terms”) govern use of <strong>{$brand}</strong> (“we”, “us”, or “our”) by {$audience}, including our website and mobile applications available at <a href="{$website}">{$website}</a>.</p>
<p>By creating an account or using the services, you agree to these Terms and our Privacy Policy. If you do not agree, do not use the services.</p>

<h3>1. Eligibility</h3>
<p>You must be legally able to enter a binding agreement in your country. If you use the platform on behalf of a business, you represent that you have authority to bind that business.</p>

<h3>2. Accounts</h3>
<ul>
  <li>Provide accurate registration information and keep it updated.</li>
  <li>Keep your login credentials confidential.</li>
  <li>You are responsible for activity under your account.</li>
  <li>You may delete your account in the app or by contacting support where that feature is available.</li>
</ul>

<h3>3. Marketplace Roles</h3>
<p>{$brand} provides a marketplace platform. Depending on the product you use, you may act as a customer, seller/vendor, or delivery partner. You are responsible for complying with local laws applicable to your role (including selling, shipping, delivery, and tax obligations where relevant).</p>

<h3>4. Orders, Payments &amp; Deliveries</h3>
<ul>
  <li>Order acceptance, pricing, shipping, and fulfillment follow the information shown at checkout or assignment.</li>
  <li>Payments may be processed by third-party payment providers.</li>
  <li>Physical goods purchased through the marketplace are sold by merchants/sellers on the platform (unless otherwise stated). Platform fees and seller subscriptions, if any, may be governed by separate merchant agreements and/or web billing.</li>
  <li>Digital content unlockable only inside iOS apps is not sold through non-Apple in-app purchase mechanisms when prohibited by Apple rules.</li>
</ul>

<h3>5. Acceptable Use</h3>
<p>You agree not to:</p>
<ul>
  <li>Violate laws or third-party rights</li>
  <li>Upload unlawful, harmful, infringing, or fraudulent content</li>
  <li>Attempt unauthorized access, scraping, or disruption of the service</li>
  <li>Misrepresent identity, products, delivery status, or payment information</li>
</ul>
<p>We may suspend or terminate accounts that violate these Terms.</p>

<h3>6. User Content</h3>
<p>You retain ownership of content you submit (such as product images or messages). You grant us a non-exclusive license to host, display, and use that content as needed to operate the marketplace.</p>

<h3>7. Intellectual Property</h3>
<p>The apps, website, branding, and software are owned by {$brand} or its licensors. You may not copy, reverse engineer, or redistribute them except as allowed by law.</p>

<h3>8. Disclaimers</h3>
<p>The services are provided “as is” and “as available.” To the maximum extent permitted by law, we disclaim warranties of merchantability, fitness for a particular purpose, and non-infringement. Marketplace transactions between buyers and sellers may involve third parties for which we have limited control.</p>

<h3>9. Limitation of Liability</h3>
<p>To the maximum extent permitted by law, {$brand} is not liable for indirect, incidental, special, consequential, or punitive damages, or loss of profits, data, or goodwill arising from your use of the services.</p>

<h3>10. App Store / Play Store Terms</h3>
<p>If you downloaded an app from Apple App Store or Google Play, you also agree to comply with the applicable store terms. Those stores are third-party platforms and are not responsible for providing maintenance or support for our apps unless required by their terms.</p>

<h3>11. Termination</h3>
<p>You may stop using the services at any time and may delete your account where available. We may suspend or terminate access for violations of these Terms, suspected fraud, legal requirements, or service integrity risks.</p>

<h3>12. Changes</h3>
<p>We may update these Terms. Continued use after changes constitutes acceptance of the updated Terms.</p>

<h3>13. Contact</h3>
<p>Support and legal questions:<br>
Email: <a href="mailto:{$email}">{$email}</a><br>
Website: <a href="{$website}">{$website}</a></p>
HTML;
    }

    public static function returnAndRefundHtml(): string
    {
        $brand = self::brandName();
        $website = self::website();
        $email = self::supportEmail();
        $updated = 'July 30, 2026';

        return <<<HTML
<h2>Return and Refund Policy</h2>
<p><strong>Last updated:</strong> {$updated}</p>
<p>This Return and Refund Policy explains how returns, refunds, and order disputes work on <strong>{$brand}</strong>.</p>

<h3>1. Who Handles Returns</h3>
<p>Most products on {$brand} are sold by independent merchants. Return eligibility, restocking rules, and timelines may vary by seller and product type. Please review the seller’s listing details and shop policies before purchase.</p>

<h3>2. Requesting a Return</h3>
<ul>
  <li>Open the order in your account and follow the return / dispute options where available.</li>
  <li>Contact the seller through in-app messaging for item issues when possible.</li>
  <li>If you cannot resolve the issue with the seller, contact platform support at <a href="mailto:{$email}">{$email}</a>.</li>
</ul>

<h3>3. Eligible Returns</h3>
<p>Returns are generally considered when the item is defective, damaged in transit, not as described, or missing parts. Perishable goods, personalized items, digital goods, and hygiene-sensitive products may be non-returnable unless required by law.</p>

<h3>4. Refunds</h3>
<p>Approved refunds are issued to the original payment method when possible. Timing depends on the payment provider and bank. Partial refunds may apply for missing or partially usable items.</p>

<h3>5. Cancellations</h3>
<p>Orders can usually be cancelled only before the seller ships or confirms fulfillment. After shipment, use the return or dispute process instead.</p>

<h3>6. Contact</h3>
<p>Questions: <a href="mailto:{$email}">{$email}</a> · <a href="{$website}">{$website}</a></p>
HTML;
    }

    public static function aboutUsHtml(): string
    {
        $brand = self::brandName();
        $website = self::website();
        $email = self::supportEmail();

        return <<<HTML
<h2>About {$brand}</h2>
<p><strong>{$brand}</strong> is a marketplace that connects customers with local merchants and delivery partners through our website and mobile apps.</p>
<p>Our goal is to make shopping, selling, and delivery simple, reliable, and transparent — from discovery and checkout to fulfillment and support.</p>
<ul>
  <li>Browse products and shops near you</li>
  <li>Place orders securely</li>
  <li>Track deliveries and communicate with sellers</li>
  <li>Get help through in-app support and email</li>
</ul>
<p>Learn more at <a href="{$website}">{$website}</a> or contact us at <a href="mailto:{$email}">{$email}</a>.</p>
HTML;
    }

    public static function contactUsHtml(): string
    {
        $brand = self::brandName();
        $website = self::website();
        $email = self::supportEmail();
        $phone = e((string) (config('system_settings.support_phone') ?: ''));

        $phoneBlock = $phone !== ''
            ? "<p><strong>Phone:</strong> <a href=\"tel:{$phone}\">{$phone}</a></p>"
            : '';

        return <<<HTML
<h2>Contact Us</h2>
<p>We are here to help with orders, account questions, seller support, and delivery issues on <strong>{$brand}</strong>.</p>
<p><strong>Email:</strong> <a href="mailto:{$email}">{$email}</a></p>
{$phoneBlock}
<p><strong>Website:</strong> <a href="{$website}">{$website}</a></p>
<p>You can also use the contact form on this page. Please include your order number when writing about a purchase.</p>
HTML;
    }
}
