/**
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

package org.apache.cxf.management.web.logging.logbrowser.client.ui.browser;

import java.util.List;
import javax.annotation.Nonnull;

import com.google.inject.Inject;
import com.google.inject.Singleton;
import com.google.inject.name.Named;

import org.apache.cxf.management.web.logging.logbrowser.client.EventBus;
import org.apache.cxf.management.web.logging.logbrowser.client.event.ChangedSubscriptionsEvent;
import org.apache.cxf.management.web.logging.logbrowser.client.event.ChangedSubscriptionsEventHandler;
import org.apache.cxf.management.web.logging.logbrowser.client.event.GoToEditCriteriaEvent;
import org.apache.cxf.management.web.logging.logbrowser.client.event.GoToSettingsEvent;
import org.apache.cxf.management.web.logging.logbrowser.client.event.SelectedSubscriptionEvent;
import org.apache.cxf.management.web.logging.logbrowser.client.service.settings.SettingsFacade;
import org.apache.cxf.management.web.logging.logbrowser.client.service.settings.Subscription;
import org.apache.cxf.management.web.logging.logbrowser.client.ui.BasePresenter;
import org.apache.cxf.management.web.logging.logbrowser.client.ui.BindStrategy;

@Singleton
public class NavigationSidebarPresenter extends BasePresenter implements NavigationSidebarView.Presenter {

    @Nonnull
    private final NavigationSidebarView view;

    @Nonnull
    private final SettingsFacade settingsManager;

    private List<Subscription> subscriptions;

    @Inject
    public NavigationSidebarPresenter(@Nonnull final EventBus eventBus,
            @Nonnull final NavigationSidebarView view,
            @Nonnull @Named("BindStrategyForNavigationSidebar") final BindStrategy bindStrategy,
            @Nonnull final SettingsFacade settingsManager) {
        super(eventBus, view, bindStrategy);

        this.view = view;
        this.view.setPresenter(this);

        this.settingsManager = settingsManager;

        bind();

        updateSubscriptions();
    }

    public void onSubcriptionItemClicked(final int row) {
        assert row >= 0 && row < subscriptions.size();
        Subscription selectedSubscription = subscriptions.get(row);
        eventBus.fireEvent(new SelectedSubscriptionEvent(selectedSubscription.getUrl()));
    }

    public void onManageSubscriptionsButtonClicked() {
        eventBus.fireEvent(new GoToSettingsEvent());
    }

    public void onEditCriteriaHyperinkClicked() {
        eventBus.fireEvent(new GoToEditCriteriaEvent());
    }

    private void updateSubscriptions() {
        subscriptions = settingsManager.getSubscriptions();
        view.setSubscriptions(subscriptions);
    }

    private void bind() {
        eventBus.addHandler(ChangedSubscriptionsEvent.TYPE, new ChangedSubscriptionsEventHandler() {

            public void onChangedSubscriptions(ChangedSubscriptionsEvent event) {
                updateSubscriptions();
            }
        });

    }
}