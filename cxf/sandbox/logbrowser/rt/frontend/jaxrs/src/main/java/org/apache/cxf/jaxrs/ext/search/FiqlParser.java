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
package org.apache.cxf.jaxrs.ext.search;

import java.text.DateFormat;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Date;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import javax.xml.datatype.DatatypeConfigurationException;
import javax.xml.datatype.DatatypeFactory;

import org.apache.cxf.jaxrs.utils.InjectionUtils;

/**
 * Parses <a href="http://tools.ietf.org/html/draft-nottingham-atompub-fiql-00">FIQL</a> expression to
 * construct {@link SearchCondition} structure. Since this class operates on Java type T, not on XML
 * structures "selectors" part of specification is not applicable; instead selectors describes getters of type
 * T used as search condition type (see {@link SimpleSearchCondition#isMet(Object)} for details.
 * 
 * @param <T> type of search condition.
 */
public class FiqlParser<T> {

    public static final String OR = ",";
    public static final String AND = ";";

    public static final String GT = "=gt=";
    public static final String GE = "=ge=";
    public static final String LT = "=lt=";
    public static final String LE = "=le=";
    public static final String EQ = "==";
    public static final String NEQ = "!=";

    private static Map<String, ConditionType> operatorsMap;
    static {
        operatorsMap = new HashMap<String, ConditionType>();
        operatorsMap.put(GT, ConditionType.GREATER_THAN);
        operatorsMap.put(GE, ConditionType.GREATER_OR_EQUALS);
        operatorsMap.put(LT, ConditionType.LESS_THAN);
        operatorsMap.put(LE, ConditionType.LESS_OR_EQUALS);
        operatorsMap.put(EQ, ConditionType.EQUALS);
        operatorsMap.put(NEQ, ConditionType.NOT_EQUALS);
    }

    private Beanspector<T> beanspector;

    /**
     * Creates FIQL parser.
     * 
     * @param tclass - class of T used to create condition objects in built syntax tree. Class T must have
     *            accessible no-arg constructor and complementary setters to these used in FIQL expressions.
     */
    public FiqlParser(Class<T> tclass) {
        beanspector = new Beanspector<T>(tclass);
    }

    /**
     * Parses expression and builds search filter. Names used in FIQL expression are names of getters/setters
     * in type T.
     * <p>
     * Example:
     * 
     * <pre>
     * class Condition {
     *   public String getFoo() {...}
     *   public void setFoo(String foo) {...}
     *   public int getBar() {...}
     *   public void setBar(int bar) {...}
     * }
     * 
     * FiqlParser&lt;Condition> parser = new FiqlParser&lt;Condition&gt;(Condition.class);
     * parser.parse("foo==mystery*;bar=ge=10");
     * </pre>
     * 
     * @param fiqlExpression expression of filter.
     * @return tree of {@link SearchCondition} objects representing runtime search structure.
     * @throws FiqlParseException when expression does not follow FIQL grammar
     */
    public SearchCondition<T> parse(String fiqlExpression) throws FiqlParseException {
        ASTNode<T> ast = parseAndsOrsParens(fiqlExpression);
        // System.out.println(ast);
        return ast.build();
    }

    private ASTNode<T> parseAndsOrsParens(String expr) throws FiqlParseException {
        String s1 = "([\\p{ASCII}&&[^;,()]]+|\\([\\p{ASCII}]+\\))([;,])?";
        Pattern p = Pattern.compile(s1);
        Matcher m = p.matcher(expr);
        List<String> subexpressions = new ArrayList<String>();
        List<String> operators = new ArrayList<String>();
        int lastEnd = -1;
        while (m.find()) {
            subexpressions.add(m.group(1));
            operators.add(m.group(2));
            if (lastEnd != -1 && lastEnd != m.start()) {
                throw new FiqlParseException(String
                    .format("Unexpected characters \"%s\" starting at position %d", expr.substring(lastEnd, m
                        .start()), lastEnd));
            }
            lastEnd = m.end();
        }
        if (operators.get(operators.size() - 1) != null) {
            String op = operators.get(operators.size() - 1);
            String ex = subexpressions.get(subexpressions.size() - 1);
            throw new FiqlParseException("Dangling operator at the end of expression: ..." + ex + op);
        }
        // looking for adjacent ANDs then group them into ORs
        // Note: in case not ANDs is found (e.g only ORs) every single subexpression is
        // treated as "single item group of ANDs"
        int from = 0;
        int to = 0;
        SubExpression ors = new SubExpression(OR);
        while (to < operators.size()) {
            while (to < operators.size() && AND.equals(operators.get(to))) {
                to++;
            }
            SubExpression ands = new SubExpression(AND);
            for (; from <= to; from++) {
                String subex = subexpressions.get(from);
                ASTNode<T> node = null;
                if (subex.startsWith("(")) {
                    node = parseAndsOrsParens(subex.substring(1, subex.length() - 1));
                } else {
                    node = parseComparison(subex);
                }
                ands.add(node);
            }
            to = from;
            if (ands.getSubnodes().size() == 1) {
                ors.add(ands.getSubnodes().get(0));
            } else {
                ors.add(ands);
            }
        }
        if (ors.getSubnodes().size() == 1) {
            return ors.getSubnodes().get(0);
        } else {
            return ors;
        }
    }

    private Comparison parseComparison(String expr) throws FiqlParseException {
        String comparators = GT + "|" + GE + "|" + LT + "|" + LE + "|" + EQ + "|" + NEQ;
        String s1 = "[\\p{ASCII}]+(" + comparators + ")";
        Pattern p = Pattern.compile(s1);
        Matcher m = p.matcher(expr);
        if (m.find()) {
            String name = expr.substring(0, m.start(1));
            String operator = m.group(1);
            String value = expr.substring(m.end(1));
            if ("".equals(value)) {
                throw new FiqlParseException("Not a comparison expression: " + expr);
            }
            Object castedValue = parseDatatype(name, value);
            return new Comparison(name, operator, castedValue);
        } else {
            throw new FiqlParseException("Not a comparison expression: " + expr);
        }
    }

    private Object parseDatatype(String setter, String value) throws FiqlParseException {
        Object castedValue = value;
        Class<?> valueType;
        try {
            valueType = beanspector.getAccessorType(setter);
        } catch (Exception e) {
            throw new FiqlParseException(e);
        }
        if (Date.class.isAssignableFrom(valueType)) {
            DateFormat df;
            try {
                df = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSSZ");
                // zone in XML is "+01:00" in Java is "+0100"; stripping semicolon
                int idx = value.lastIndexOf(':');
                if (idx != -1) {
                    String v = value.substring(0, idx) + value.substring(idx + 1);
                    castedValue = df.parse(v);
                } else {
                    castedValue = df.parse(value);
                }
            } catch (ParseException e) {
                // is that duration?
                try {
                    Date now = new Date();
                    DatatypeFactory.newInstance().newDuration(value).addTo(now);
                    castedValue = now;
                } catch (DatatypeConfigurationException e1) {
                    throw new FiqlParseException(e1);
                } catch (IllegalArgumentException e1) {
                    throw new FiqlParseException("Can parse " + value + " neither as date nor duration", e);
                }
            }
        } else {
            try {
                castedValue = InjectionUtils.convertStringToPrimitive(value, valueType);
            } catch (Exception e) {
                throw new FiqlParseException("Cannot convert String value \"" + value
                                             + "\" to a value of class " + valueType.getName(), e);
            }
        }
        return castedValue;
    }

    // node of abstract syntax tree
    private interface ASTNode<T> {
        SearchCondition<T> build() throws FiqlParseException;
    }

    private class SubExpression implements ASTNode<T> {
        private String operator;
        private List<ASTNode<T>> subnodes = new ArrayList<ASTNode<T>>();

        public SubExpression(String operator) {
            this.operator = operator;
        }

        public void add(ASTNode<T> node) {
            subnodes.add(node);
        }

        public List<ASTNode<T>> getSubnodes() {
            return Collections.unmodifiableList(subnodes);
        }

        @Override
        public String toString() {
            String s = operator.equals(AND) ? "AND" : "OR";
            s += ":[";
            for (int i = 0; i < subnodes.size(); i++) {
                s += subnodes.get(i);
                if (i < subnodes.size() - 1) {
                    s += ", ";
                }
            }
            s += "]";
            return s;
        }

        public SearchCondition<T> build() throws FiqlParseException {
            boolean hasSubtree = false;
            for (ASTNode<T> node : subnodes) {
                if (node instanceof FiqlParser.SubExpression) {
                    hasSubtree = true;
                    break;
                }
            }
            if (!hasSubtree && AND.equals(operator)) {
                try {
                    // Optimization: single SimpleSearchCondition for 'AND' conditions
                    Map<String, ConditionType> map = new HashMap<String, ConditionType>();
                    beanspector.instantiate();
                    for (ASTNode<T> node : subnodes) {
                        FiqlParser<T>.Comparison comp = (Comparison)node;
                        map.put(comp.getName(), operatorsMap.get(comp.getOperator()));
                        beanspector.setValue(comp.getName(), comp.getValue());
                    }
                    return new SimpleSearchCondition<T>(map, beanspector.getBean());
                } catch (Throwable e) {
                    throw new RuntimeException(e);
                }
            } else {
                List<SearchCondition<T>> scNodes = new ArrayList<SearchCondition<T>>();
                for (ASTNode<T> node : subnodes) {
                    scNodes.add(node.build());
                }
                if (OR.equals(operator)) {
                    return new OrSearchCondition<T>(scNodes);
                } else {
                    return new AndSearchCondition<T>(scNodes);
                }
            }
        }
    }

    private class Comparison implements ASTNode<T> {
        private String name;
        private String operator;
        private Object value;

        public Comparison(String name, String operator, Object value) {
            this.name = name;
            this.operator = operator;
            this.value = value;
        }

        public String getName() {
            return name;
        }

        public String getOperator() {
            return operator;
        }

        public Object getValue() {
            return value;
        }

        @Override
        public String toString() {
            return name + " " + operator + " " + value + " (" + value.getClass().getSimpleName() + ")";
        }

        public SearchCondition<T> build() throws FiqlParseException {
            T cond = createTemplate(name, value);
            ConditionType ct = operatorsMap.get(operator);
            return new SimpleSearchCondition<T>(ct, cond);
        }

        private T createTemplate(String setter, Object val) throws FiqlParseException {
            try {
                beanspector.instantiate().setValue(setter, val);
                return beanspector.getBean();
            } catch (Throwable e) {
                throw new FiqlParseException(e);
            }
        }
    }
}
